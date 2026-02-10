<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\EvaluacionHelper;
use App\Services\BigQueryService;
use App\Services\ImageUploadService;
use App\Services\TechnicalErrorLogger;
use App\Services\DataFetchService;
use App\Services\FormProcessingService;

/**
 * 📋 MONITOREO DE ERRORES TÉCNICOS:
 * 
 * Los errores técnicos se registran automáticamente en:
 *   📁 storage/logs/errores-tecnicos.log
 * 
 * Para ver los errores en tiempo real desde terminal:
 *   tail -f storage/logs/errores-tecnicos.log
 * 
 * O desde PowerShell:
 *   Get-Content storage/logs/errores-tecnicos.log -Wait
 * 
 * Los errores contienen:
 *   - Método donde ocurrió (saveDatos, saveSeccionIndividual, etc)
 *   - session_id del formulario afectado
 *   - Mensaje de error técnico completo
 *   - IP del usuario
 *   - URL de la solicitud
 */
class FormularioController extends Controller
{
    protected $bigQueryService;
    protected $imageUpload;
    protected $errorLogger;
    protected $dataFetch;
    protected $formProcessing;

    public function __construct(
        BigQueryService $bigQueryService,
        ImageUploadService $imageUpload,
        TechnicalErrorLogger $errorLogger,
        DataFetchService $dataFetch,
        FormProcessingService $formProcessing
    ) {
        $this->bigQueryService = $bigQueryService;
        $this->imageUpload = $imageUpload;
        $this->errorLogger = $errorLogger;
        $this->dataFetch = $dataFetch;
        $this->formProcessing = $formProcessing;
        
        Log::info('✅ Servicios inicializados', [
            'BigQueryService' => 'OK',
            'ImageUploadService' => 'OK',
            'TechnicalErrorLogger' => 'OK',
            'DataFetchService' => 'OK',
            'FormProcessingService' => 'OK'
        ]);
    }

    /**
     * 🆕 PASO 1: Guardar "DATOS" (correo, modalidad, etc)
     * Se ejecuta cuando hace click en "Continuar" en vista "datos"
     * INSERT inicial que genera session_id
     */
    public function saveDatos(Request $request)
    {
        try {
            $datos = [
                'fecha_hora_inicio' => $request->input('fecha_hora_inicio'),
                'correo_realizo' => $request->input('correo_realizo'),
                'lider_zona' => $request->input('lider_zona'),
                'tienda' => $request->input('tienda'),
                'ubicacion' => $request->input('ubicacion'),
                'pais' => $request->input('pais'),
                'zona' => $request->input('zona'),
                'modalidad_visita' => $request->input('modalidad_visita')
            ];

            $resultado = $this->bigQueryService->crearFormulario($datos);

            if ($resultado['success']) {
                session(['form_session_id' => $resultado['session_id']]);
                return response()->json($resultado);
            } else {
                $this->errorLogger->registrarSiEsErrorTecnico('saveDatos', 'N/A', $resultado['message']);
                return response()->json($resultado, 400);
            }
        } catch (\Exception $e) {
            $this->errorLogger->registrar('saveDatos', 'N/A', $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            Log::error('❌ Error en saveDatos', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🆕 PASO 2-7: Guardar secciones individuales
     * Recibe: session_id, nombre_seccion, preguntas
     */
    public function saveSeccionIndividual(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $nombreSeccion = $request->input('nombre_seccion');
            $preguntas = $request->input('preguntas', []);
            $mainFields = $request->input('main_fields', []);

            if (!$sessionId || !$nombreSeccion) {
                return response()->json(['success' => false, 'message' => 'Faltan session_id o nombre_seccion'], 400);
            }

            Log::info('📤 Guardando sección individual', [
                'session_id' => $sessionId,
                'seccion' => $nombreSeccion,
                'preguntas_count' => count($preguntas),
                'main_fields_count' => count($mainFields)
            ]);

            $resultado = $this->bigQueryService->actualizarSeccion($sessionId, $nombreSeccion, $preguntas);
            $this->errorLogger->registrarSiEsErrorTecnico('saveSeccionIndividual', $sessionId, $resultado['message'] ?? '', [
                'seccion' => $nombreSeccion
            ]);

            if (!empty($mainFields) && $resultado['success']) {
                Log::info('🆕 Actualizando campos principales desde seccion-1', ['session_id' => $sessionId]);
                $this->bigQueryService->actualizarCamposPrincipales($sessionId, $mainFields);
            }

            return response()->json($resultado);
        } catch (\Exception $e) {
            $this->errorLogger->registrar('saveSeccionIndividual', $request->input('session_id', 'N/A'), $e->getMessage());
            Log::error('❌ Error en saveSeccionIndividual', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🆕 GUARDAR CAMPOS PRINCIPALES (pais, zona, tienda) desde seccion-1
     */
    public function saveMainFields(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $mainFields = $request->input('main_fields', []);

            if (!$sessionId) {
                return response()->json(['success' => false, 'message' => 'Falta session_id'], 400);
            }

            Log::info('🆕 Guardando campos principales', [
                'session_id' => $sessionId,
                'campos' => array_keys($mainFields)
            ]);

            $resultado = $this->bigQueryService->actualizarCamposPrincipales($sessionId, $mainFields);
            $this->errorLogger->registrarSiEsErrorTecnico('saveMainFields', $sessionId, $resultado['message'] ?? '');
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            $this->errorLogger->registrar('saveMainFields', $request->input('session_id', 'N/A'), $e->getMessage());
            Log::error('❌ Error en saveMainFields', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🆕 GUARDAR KPIs desde seccion-6
     */
    public function saveKPIs(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $kpis = $request->input('kpis', []);

            if (!$sessionId) {
                return response()->json(['success' => false, 'message' => 'Falta session_id'], 400);
            }

            $kpisValidos = array_filter($kpis, fn($k) => !empty($k['codigo_pregunta']) && !empty($k['valor']));

            if (empty($kpisValidos)) {
                return response()->json(['success' => false, 'message' => 'No hay KPIs válidos para guardar'], 400);
            }

            Log::info('📊 Guardando KPIs', ['session_id' => $sessionId, 'kpis_count' => count($kpisValidos)]);

            $resultado = $this->bigQueryService->actualizarKPIs($sessionId, $kpisValidos);
            $this->errorLogger->registrarSiEsErrorTecnico('saveKPIs', $sessionId, $resultado['message'] ?? '');
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            $this->errorLogger->registrar('saveKPIs', $request->input('session_id', 'N/A'), $e->getMessage());
            Log::error('❌ Error en saveKPIs', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🆕 GUARDAR PLANES (Y FINALIZAR TODO)
     * Recibe: session_id, planes
     * OBTIENE de BigQuery: secciones, kpis para calcular promedios reales
     * Calcula puntajes basados en DATOS REALES de BigQuery
     */
    public function savePlanes(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $planesEnviados = $request->input('planes', []);

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta session_id'
                ], 400);
            }

            Log::info('📋 GUARDAR PLANES - Obteniendo datos reales de BigQuery', [
                'session_id' => $sessionId,
                'planes_recibidos' => count($planesEnviados)
            ]);

            // 🆕 CONSULTAR BigQuery para obtener datos REALES (no los del frontend)
            $registroBQ = $this->bigQueryService->obtenerRegistro($sessionId);

            if (!$registroBQ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado en BigQuery'
                ], 400);
            }

            // 🆕 PROCESAR SECCIONES desde BigQuery
            $secciones = [];
            if (!empty($registroBQ['secciones'])) {
                $seccionesData = is_string($registroBQ['secciones']) 
                    ? json_decode($registroBQ['secciones'], true) 
                    : (array)$registroBQ['secciones'];
                $secciones = is_array($seccionesData) ? $seccionesData : [];
            }

            // 🆕 PROCESAR KPIs desde BigQuery
            $kpis = [];
            if (!empty($registroBQ['kpis'])) {
                $kpisData = is_string($registroBQ['kpis']) 
                    ? json_decode($registroBQ['kpis'], true) 
                    : (array)$registroBQ['kpis'];
                
                if (is_array($kpisData)) {
                    foreach ($kpisData as $kpi) {
                        if (is_object($kpi)) {
                            $kpis[] = [
                                'codigo_pregunta' => $kpi->codigo_pregunta ?? $kpi['codigo_pregunta'] ?? '',
                                'valor' => $kpi->valor ?? $kpi['valor'] ?? '',
                                'variacion' => $kpi->variacion ?? $kpi['variacion'] ?? null
                            ];
                        } elseif (is_array($kpi)) {
                            $kpis[] = $kpi;
                        }
                    }
                }
            }

            Log::info('📊 Datos obtenidos de BigQuery', [
                'session_id' => $sessionId,
                'secciones_count' => count($secciones),
                'kpis_count' => count($kpis),
                'planes_recibidos' => count($planesEnviados)
            ]);

            $resumen = $this->formProcessing->calcularResumen($secciones, $kpis);
            $resumenAreas = $resumen['resumen_areas'];
            $totales = ['puntaje' => $resumen['puntos_totales'], 'estrellas' => $resumen['estrellas']];

            // 🆕 PREPARAR DATOS PARA ACTUALIZAR BIGQUERY
            $datosFinales = [
                'planes' => $planesEnviados,
                'fecha_hora_fin' => now()->toIso8601String(),
                'secciones' => $secciones,
                'kpis' => $kpis,
                'resumen_areas' => $resumenAreas,
                'puntos_totales' => $totales['puntaje'],
                'estrellas' => $totales['estrellas'],
            ];

            // 🆕 GUARDAR EN BIGQUERY USANDO EL SERVICIO
            $resultado = $this->bigQueryService->actualizarPlanesYFinalizar($sessionId, $datosFinales);

            if (!$resultado['success']) {
                Log::error('❌ Error al guardar planes y finalizar', [
                    'error' => $resultado['message'],
                    'session_id' => $sessionId
                ]);
                $this->errorLogger->registrarSiEsErrorTecnico('savePlanes', $sessionId, $resultado['message'] ?? '');
                
                return response()->json($resultado, 400);
            }

            Log::info('✅ Planes guardados y formulario finalizado exitosamente', [
                'session_id' => $sessionId,
                'planes_guardados' => count($planesEnviados),
                'puntos_calculados' => $totales['puntaje'],
                'estrellas_calculadas' => $totales['estrellas'],
                'html_ruta' => $resultado['html_ruta'] ?? 'N/A'
            ]);

            return response()->json($resultado);

        } catch (\Exception $e) {
            $this->errorLogger->registrar('savePlanes', $request->input('session_id', 'N/A'), $e->getMessage());

            Log::error('❌ Error en savePlanes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🆕 PASO FINAL: Completar formulario
     * Se ejecuta al final después de guardar Planes
     * ⚠️ IMPORTANTE: Los KPIS ya se guardaron en saveKPIs() y PLANES en savePlanes()
     * Aquí se calculan RESUMEN y se marca fecha_hora_fin, pero SIN duplicar KPIs/Planes
     */
    public function finalizarFormulario(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $kpis = $request->input('kpis', []); // Para calcular promedios, pero NO se envía a BQ
            $secciones = $request->input('secciones', []);
            $planes = $request->input('planes', []); // 🆕 RECIBIR LOS PLANES TAMBIÉN

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta session_id'
                ], 400);
            }

            // Calcular promedios y totales usando secciones y kpis (SOLO para el resumen)
            $promediosPorArea = EvaluacionHelper::calcularPromediosPorArea($secciones, $kpis);
            $totales = EvaluacionHelper::calcularTotalPonderado($promediosPorArea);

            $resumenAreas = [];
            foreach ($promediosPorArea as $area => $info) {
                $resumenAreas[] = [
                    'nombre' => ucfirst($area),
                    'puntos' => $info['promedio'] ?? 'N/A',
                    'estrellas' => isset($info['promedio']) ? intval(round($info['promedio'] / 0.2)) : 'N/A',
                ];
            }

            // 🆕 INCLUIR PLANES EN DATOS FINALES PARA GENERAR HTML
            $datosFinales = [
                'fecha_hora_fin' => now()->toIso8601String(),
                'kpis' => [], // Vacío - ya guardados en saveKPIs()
                'planes' => $planes, // 🆕 INCLUIR PLANES PARA GENERAR HTML
                'resumen_areas' => $resumenAreas,
                'puntos_totales' => $totales['puntaje'],
                'estrellas' => $totales['estrellas']
            ];

            Log::info('✅ Finalizando formulario', [
                'session_id' => $sessionId,
                'fecha_fin' => $datosFinales['fecha_hora_fin'],
                'puntos_totales' => $totales['puntaje'],
                'estrellas' => $totales['estrellas'],
                'planes_count' => count($planes)
            ]);

            // Si vienen planes en la request, guardarlos explícitamente antes de finalizar
            if (!empty($planes)) {
                Log::info('📥 Se reciben planes en finalizarFormulario, guardando en BigQuery', [
                    'session_id' => $sessionId,
                    'planes_count' => count($planes)
                ]);

                $resPlanes = $this->bigQueryService->actualizarPlanes($sessionId, $planes);
                $this->errorLogger->registrarSiEsErrorTecnico('finalizarFormulario.actualizarPlanes', $sessionId, $resPlanes['message'] ?? '');

                if (empty($resPlanes['success']) || $resPlanes['success'] === false) {
                    Log::warning('⚠️ No se pudieron guardar los planes durante finalizarFormulario', [
                        'session_id' => $sessionId,
                        'respuesta_planes' => $resPlanes
                    ]);
                }
            }

            $resultado = $this->bigQueryService->finalizarFormulario($sessionId, $datosFinales);

            return response()->json($resultado);
        } catch (\Exception $e) {
            Log::error('❌ Error en finalizarFormulario', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('session_id', 'N/A')
            ]);

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function mostrarFormulario()
    {
        $formId = uniqid('form_', true);
        session(['form_id' => $formId]);
        return view('formulario', ['resultado' => []]);
    }

    public function ObtenerPaises()
    {
        $paises = $this->dataFetch->obtenerPaises();
        return response()->json($paises);
    }

    public function obtenerZonas($bv_pais)
    {
        $zonas = $this->dataFetch->obtenerZonas($bv_pais);
        return response()->json($zonas);
    }

    public function obtenerTiendas($bv_pais, $zona)
    {
        $tiendas = $this->dataFetch->obtenerTiendas($bv_pais, $zona);
        return response()->json($tiendas);
    }

    public function subirImagenIncremental(Request $request)
    {
        try {
            $fieldName = $request->input('field_name');

            if (!$request->hasFile('image')) {
                return response()->json(['error' => 'No se recibió ninguna imagen'], 400);
            }

            $file = $request->file('image');
            $validacionTamano = $this->imageUpload->validarTamano($file);
            if (!$validacionTamano['valid']) {
                return response()->json(['error' => $validacionTamano['error']], 413);
            }

            $validacionTipo = $this->imageUpload->validarTipo($file);
            if (!$validacionTipo['valid']) {
                return response()->json(['error' => $validacionTipo['error']], 415);
            }

            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }

            Log::info("📤 Subiendo imagen individual", ['field_name' => $fieldName]);

            $publicUrl = $this->imageUpload->subirImagenOptimizada($file, $fieldName);

            if ($publicUrl) {
                session(["uploaded_images.{$fieldName}" => $publicUrl]);
                return response()->json([
                    'success' => true,
                    'url' => $publicUrl,
                    'field_name' => $fieldName,
                    'message' => 'Imagen subida correctamente'
                ]);
            } else {
                return response()->json(['error' => 'Error al subir la imagen al storage'], 500);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en subida incremental', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function guardarSeccion(Request $request)
    {
        try {
            set_time_limit(180);
            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }
            
            $formId = session('token_unico');
            $tiendaCompleta = $request->input('tienda');
            $correoOriginal = $request->input('correo_realizo');

            $data = [
                'id' => uniqid(),
                'session_id' => $formId,
                'fecha_hora_inicio' => $request->input('fecha_hora_inicio'),
                'fecha_hora_fin' => now(),
                'correo_realizo' => $correoOriginal,
                'lider_zona' => $request->input('lider_zona'),
                'tienda' => $tiendaCompleta,
                'ubicacion' => $request->input('ubicacion'),
                'pais' => $request->input('pais'),
                'zona' => $request->input('zona'),
                'modalidad' => $request->input('modalidad_visita'),
            ];

            $data['secciones'] = $request->input('secciones', []);
            $data['planes'] = $request->input('planes', []);
            $data['kpis'] = $request->input('kpis', []);

            // Validar imágenes obligatorias
            $validacion = $this->formProcessing->validarImagenesObligatorias($data['secciones']);
            if (!$validacion['valido']) {
                return response()->json(['error' => $validacion['error']], 422);
            }

            $this->formProcessing->normalizarURLsImagenes($data['secciones']);

            // === LOG ===
            Log::info("✅ Estructura final lista para insertar", ['session_id' => $formId, 'secciones' => count($data['secciones'])]);

            // === INSERTAR EN BIGQUERY USANDO BIGQUERYSERVICE ===
            $table = $this->bigQueryService->obtenerTabla();
            $insertResponse = $table->insertRows([['data' => $data]]);

            if ($insertResponse->isSuccessful()) {
                session()->forget('uploaded_images');

                try {
                    $crmIdTienda = trim(Str::before($tiendaCompleta, ' '));
                    $crmIdTiendaCompleto = $data['pais'] . $crmIdTienda;

                    // Obtener correos usando DataFetchService
                    $correoTienda = $this->dataFetch->obtenerCorreoTienda($crmIdTiendaCompleto, $data['pais']);
                    $correoJefe = $this->dataFetch->obtenerCorreoJefe($data['pais']);

                    // Calcular resumen de puntuaciones usando FormProcessingService
                    $resumen = $this->formProcessing->calcularResumen($data['secciones'], $data['kpis']);
                    $data['resumen_areas'] = $resumen['resumen_areas'];
                    $data['puntos_totales'] = $resumen['puntos_totales'];
                    $data['estrellas'] = $resumen['estrellas'];
                    $data['correo_tienda'] = $correoTienda;
                    $data['correo_jefe_zona'] = $correoJefe;

                    // Generar HTML para correo
                    $urlHtml = $this->formProcessing->generarHTMLCorreo($data, $correoOriginal);

                    return response()->json([
                        'success' => true,
                        'message' => 'Formulario guardado correctamente y HTML generado',
                        'url_html' => $urlHtml
                    ]);
                } catch (\Exception $e) {
                    Log::error('❌ Error al crear el correo de confirmación', ['error' => $e->getMessage()]);
                    return response()->json(['success' => true, 'message' => 'Formulario guardado pero error en correo'], 200);
                }
            } else {
                Log::error('❌ Error al insertar en BigQuery', ['errores' => $insertResponse->failedRows()]);
                return response()->json(['error' => 'Error al insertar en BigQuery.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error interno al guardar sección', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }
}
