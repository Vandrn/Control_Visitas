<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
    }

    public function obtenerProgreso($sessionId)
    {
        $row = $this->bigQueryService->getProgreso($sessionId);

        return response()->json([
            'success' => true,
            'pantalla_actual' => $row['pantalla_actual'] ?? 'intro',
        ]);
    }

    public function guardarProgreso(Request $request, $sessionId)
    {
        $pantalla = $request->input('pantalla_actual', 'intro');

        $this->bigQueryService->updateProgreso($sessionId, $pantalla);

        return response()->json(['success' => true]);
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

            Log::info('📋 Detalle preguntas recibidas [' . $nombreSeccion . ']', [
                'session_id' => $sessionId,
                'preguntas' => $preguntas
            ]);

            $resultado = $this->bigQueryService->actualizarSeccion($sessionId, $nombreSeccion, $preguntas);
            $this->errorLogger->registrarSiEsErrorTecnico('saveSeccionIndividual', $sessionId, $resultado['message'] ?? '', [
                'seccion' => $nombreSeccion
            ]);

            if (!empty($mainFields) && $resultado['success']) {
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

            // 🆕 CONSULTAR BigQuery para obtener datos REALES (no los del frontend)
            Log::info('🔍 Consultando BigQuery para obtener datos de la visita', ['session_id' => $sessionId]);
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

            Log::info('📋 RESUMEN COMPLETO DE LA VISITA [' . $sessionId . ']', [
                'session_id'  => $sessionId,
                'tienda'      => $registroBQ['tienda'] ?? '',
                'pais'        => $registroBQ['pais'] ?? '',
                'zona'        => $registroBQ['zona'] ?? '',
                'correo'      => $registroBQ['correo_realizo'] ?? '',
                'modalidad'   => $registroBQ['modalidad_visita'] ?? '',
                'secciones_count' => count($secciones),
                'kpis_count'      => count($kpis),
                'planes_count'    => count($planesEnviados),
                'secciones'   => $secciones,
                'kpis'        => $kpis,
                'planes'      => $planesEnviados,
            ]);

            $resumen = $this->formProcessing->calcularResumen($secciones, $kpis);
            $resumenAreas = $resumen['resumen_areas'];
            $totales = ['puntaje' => $resumen['puntos_totales'], 'estrellas' => $resumen['estrellas']];
            $tiendaCompleta = $registroBQ['tienda'] ?? '';
            $pais = $registroBQ['pais'] ?? '';

            $crmIdTienda = trim(Str::before($tiendaCompleta, ' '));
            $crmIdTiendaCompleto = $pais . $crmIdTienda;

            $correoTienda = $this->dataFetch->obtenerCorreoTienda($crmIdTiendaCompleto, $pais);
            $correoJefe   = $this->dataFetch->obtenerCorreoJefe($pais);

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

            $datosFinales['correo_tienda'] = $correoTienda;
            $datosFinales['correo_jefe_zona'] = $correoJefe;
            $datosFinales['tienda'] = $registroBQ['tienda'] ?? '';
            $datosFinales['zona'] = $registroBQ['zona'] ?? '';
            $datosFinales['pais'] = $registroBQ['pais'] ?? '';
            $datosFinales['correo_realizo'] = $registroBQ['correo_realizo'] ?? '';

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
                return response()->json(['success' => false, 'message' => 'No se recibió ninguna imagen'], 400);
            }

            $file = $request->file('image');
            $validacionTamano = $this->imageUpload->validarTamano($file);
            if (!$validacionTamano['valid']) {
                return response()->json(['success' => false, 'message' => $validacionTamano['error']], 413);
            }

            $validacionTipo = $this->imageUpload->validarTipo($file);
            if (!$validacionTipo['valid']) {
                return response()->json(['success' => false, 'message' => $validacionTipo['error']], 415);
            }

            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }

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
                return response()->json(['success' => false, 'message' => 'Error al subir la imagen al storage'], 500);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en subida incremental', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

}
