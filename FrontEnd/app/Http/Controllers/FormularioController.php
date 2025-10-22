<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Helpers\EvaluacionHelper;
use Illuminate\Support\Facades\File;

class FormularioController extends Controller
{
    protected $bigQuery;
    protected $storage;
    protected $bucket;

    public function __construct()
    {
        // Initialize BigQuery client
        $this->bigQuery = new BigQueryClient([
            'projectId' => config('services.google.project_id'),
            'keyFilePath' => storage_path('app/' . config('services.google.keyfile')),
        ]);

        // Initialize Google Cloud Storage client
        $this->storage = new StorageClient([
            'projectId' => config('services.google.project_id'),
            'keyFilePath' => storage_path('app/' . config('services.google.keyfile')),
        ]);

        // Get the bucket
        $this->bucket = $this->storage->bucket(config('services.google.storage_bucket'));
    }

    public function mostrarFormulario()
    {
        // Generar un nuevo identificador único para cada formulario
        $formId = uniqid('form_', true);
        session(['form_id' => $formId]);

        // Fetch data from BigQuery
        $query = sprintf(
            'SELECT * FROM `adoc-bi-dev.OPB.%s` WHERE session_id = @session_id',
            config('admin.bigquery.visitas_table')
        );
        $queryJobConfig = $this->bigQuery->query($query)->parameters(['session_id' => $formId]);
        $resultados = $this->bigQuery->runQuery($queryJobConfig);

        // Convertir los resultados a un array
        $resultadoArray = [];
        foreach ($resultados->rows() as $row) {
            $resultadoArray[] = (array) $row;
        }

        // Pass the data to the view
        return view('formulario', ['resultado' => $resultadoArray]);
    }

    public function ObtenerPaises()
    {
        $query = "
            SELECT DISTINCT T001W.LAND1 AS BV_PAIS
            FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
            WHERE ADRC.COUNTRY IN ('SV', 'GT', 'HN', 'CR', 'NI', 'PA')
            AND T001W.VLFKZ = 'A'
            AND ADRC.PO_BOX <> 'CL'
            AND ADRC.SORT1 NOT IN ('WHS','BT1')
            AND TVKTT.SPRAS = 'S'
            AND ADR6.SMTP_ADDR IS NOT NULL
        ";

        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $paises = [];
        foreach ($results->rows() as $row) {
            $paises[] = [
                'label' => $row['BV_PAIS'],
                'value' => $row['BV_PAIS']
            ];
        }

        return response()->json($paises);
    }

    public function obtenerZonas($bv_pais)
    {
        $query = "
            SELECT DISTINCT ADRC.NAME3 AS ZONA
            FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
            LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
            WHERE T001W.LAND1 = @bv_pais
            AND T001W.VLFKZ = 'A'
            AND ADRC.PO_BOX <> 'CL'
            AND ADRC.SORT1 NOT IN ('WHS','BT1')
            AND TVKTT.SPRAS = 'S'
            AND ADR6.SMTP_ADDR IS NOT NULL
        ";

        $queryJobConfig = $this->bigQuery->query($query)->parameters([
            'bv_pais' => $bv_pais
        ]);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $zonas = [];
        foreach ($results->rows() as $row) {
            $zona = $row['ZONA'];
            // Si es Panamá, normaliza cualquier variante de 'Zona I' a 'Zona I'
            if ($bv_pais === 'PA' && preg_match('/^zona i$/i', $zona)) {
                $zona = 'Zona I';
            }
            $zonas[] = $zona;
        }
        // Si es Panamá, mostrar solo 'Zona I' si existe alguna variante
        if ($bv_pais === 'PA') {
            $tieneZonaI = false;
            foreach ($zonas as $z) {
                if (preg_match('/^zona i$/i', $z)) {
                    $tieneZonaI = true;
                    break;
                }
            }
            if ($tieneZonaI) {
                $zonas = ['Zona I'];
            }
        }
        return response()->json($zonas);
    }

    public function obtenerTiendas($bv_pais, $zona)
    {
        // Si es Panamá y la zona es 'Zona I', buscar ambas variantes
        if ($bv_pais === 'PA' && preg_match('/^zona i$/i', $zona)) {
            $query = "
                SELECT 
                    ADRC.NAME1 AS TIENDA,
                    ADRC.NAME2 AS UBICACION,
                    CONCAT(GEO.LATITUD, ',', GEO.LONGITUD) AS GEO
                FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                    ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
                LEFT JOIN `adoc-bi-prd`.`BI_Repo_Qlik.DIM_GEOLOCALIZACION` AS GEO ON GEO.WERKS = CAST(T001W.WERKS AS INT64)
                WHERE T001W.LAND1 = @bv_pais
                AND (ADRC.NAME3 = 'Zona I' OR ADRC.NAME3 = 'ZONA I')
                AND T001W.VLFKZ = 'A'
                AND ADRC.PO_BOX <> 'CL'
                AND ADRC.SORT1 NOT IN ('WHS','BT1')
                AND TVKTT.SPRAS = 'S'
                AND ADR6.SMTP_ADDR IS NOT NULL
            ";
            $queryJobConfig = $this->bigQuery->query($query)->parameters([
                'bv_pais' => $bv_pais
            ]);
        } else {
            $query = "
                SELECT 
                    ADRC.NAME1 AS TIENDA,
                    ADRC.NAME2 AS UBICACION,
                    CONCAT(GEO.LATITUD, ',', GEO.LONGITUD) AS GEO
                FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS T001W
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNVV` AS KNVV
                    ON KNVV.KUNNR = T001W.KUNNR AND T001W.VKORG = KNVV.VKORG AND T001W.VTWEG = KNVV.VTWEG
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS KNA1 ON KNA1.KUNNR = KNVV.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`WRF1` AS WRF1 ON WRF1.LOCNR = KNA1.KUNNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS ADRC ON ADRC.ADDRNUMBER = KNA1.ADRNR
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADR6` AS ADR6 ON ADR6.ADDRNUMBER = ADRC.ADDRNUMBER
                LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`TVKTT` AS TVKTT ON TVKTT.MANDT = T001W.MANDT AND KNVV.KTGRD = TVKTT.KTGRD
                LEFT JOIN `adoc-bi-prd`.`BI_Repo_Qlik.DIM_GEOLOCALIZACION` AS GEO ON GEO.WERKS = CAST(T001W.WERKS AS INT64)
                WHERE T001W.LAND1 = @bv_pais
                AND ADRC.NAME3 = @zona
                AND T001W.VLFKZ = 'A'
                AND ADRC.PO_BOX <> 'CL'
                AND ADRC.SORT1 NOT IN ('WHS','BT1')
                AND TVKTT.SPRAS = 'S'
                AND ADR6.SMTP_ADDR IS NOT NULL
            ";
            $queryJobConfig = $this->bigQuery->query($query)->parameters([
                'bv_pais' => $bv_pais,
                'zona' => $zona
            ]);
        }
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $tiendas = [];
        foreach ($results->rows() as $row) {
            $tiendas[] = [
                'TIENDA' => $row['TIENDA'],       // Solo NAME1, sin concatenación
                'UBICACION' => $row['UBICACION'], // Guardado si lo necesitas internamente
                'GEO' => $row['GEO'] ?? null
            ];
        }

        return response()->json($tiendas);
    }

    /**
     * 🆕 MÉTODO MEJORADO: Subir imagen individual con validación estricta de 6MB
     */
    public function subirImagenIncremental(Request $request)
    {
        try {
            $fieldName = $request->input('field_name');

            if (!$request->hasFile('image')) {
                return response()->json(['error' => 'No se recibió ninguna imagen'], 400);
            }

            $file = $request->file('image');

            // 🔒 VALIDACIÓN ESTRICTA DE TAMAÑO (6MB máximo)
            $maxSizeBytes = 6 * 1024 * 1024; // 6MB
            if ($file->getSize() > $maxSizeBytes) {
                $sizeMB = round($file->getSize() / (1024 * 1024), 2);
                return response()->json([
                    'error' => "Imagen demasiado grande: {$sizeMB}MB. Máximo permitido: 6MB"
                ], 413);
            }

            // Verificar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                return response()->json([
                    'error' => 'Tipo de archivo no permitido. Solo: JPEG, PNG, WebP'
                ], 415);
            }

            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }

            Log::info("📤 Subiendo imagen individual", [
                'field_name' => $fieldName,
                'original_size' => round($file->getSize() / (1024 * 1024), 2) . 'MB',
                'mime_type' => $file->getMimeType()
            ]);

            // 🚀 SUBIR CON COMPRESIÓN ADICIONAL EN SERVIDOR
            $publicUrl = $this->uploadImageToCloudStorageOptimized($file, $fieldName);

            if ($publicUrl) {
                // Guardar URL en sesión para uso posterior
                session(["uploaded_images.{$fieldName}" => $publicUrl]);

                Log::info("✅ Imagen subida exitosamente", [
                    'field_name' => $fieldName,
                    'url' => $publicUrl
                ]);

                return response()->json([
                    'success' => true,
                    'url' => $publicUrl,
                    'field_name' => $fieldName,
                    'message' => 'Imagen subida correctamente',
                    'size_info' => 'Comprimida y optimizada'
                ]);
            } else {
                return response()->json(['error' => 'Error al subir la imagen al storage'], 500);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en subida incremental', [
                'error' => $e->getMessage(),
                'field_name' => $request->input('field_name'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarSeccion(Request $request)
    {
        try {
            set_time_limit(180); // 3 minutos de tiempo máximo
            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }
            $formId = session('token_unico');
            $tiendaCompleta = $request->input('tienda');

            // === DATOS BASE (orden según esquema) ===
            $correoOriginal = $request->input('correo_realizo');
            $correoParaGuardar = $correoOriginal;
            if ($correoOriginal === 'erick.cruz@empresasadoc.com') {
                $correoParaGuardar = 'belen.perez@empresasadoc.com';
            }
            $data = [
                'id' => uniqid(),
                'session_id' => $formId,
                'fecha_hora_inicio' => $request->input('fecha_hora_inicio'),
                'fecha_hora_fin' => now(),
                'correo_realizo' => $correoParaGuardar,
                'lider_zona' => $request->input('lider_zona'),
                'tienda' => $tiendaCompleta,
                'ubicacion' => $request->input('ubicacion'),
                'pais' => $request->input('pais'),
                'zona' => $request->input('zona'),
                'modalidad' => $request->input('modalidad_visita'),
            ];
            
            $crmIdTienda = trim(Str::before($tiendaCompleta, ' '));
            $crmIdTiendaCompleto = $data['pais'] . $crmIdTienda;
            // Convertir código SV a nombre completo
            $nombrePais = match ($data['pais']) {
                'SV' => 'EL SALVADOR',
                'GT' => 'GUATEMALA',
                'HN' => 'HONDURAS',
                'NI' => 'NICARAGUA',
                'CR' => 'COSTA RICA',
                'PA' => 'PANAMÁ',
                default => $data['pais'], // fallback por si acaso
            };

            // === SECCIONES ===
            $data['secciones'] = $request->input('secciones', []);
            
            // Validar que las preguntas con imagen obligatoria tengan al menos una imagen
            $preguntasConImagen = [
                2 => [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 21, 22], // Operaciones
                4 => [1, 2, 5, 6, 7, 8, 9], // Producto
                5 => [1, 9] // Personal
            ];
            // Mapear nombre de sección a número para validación
            $mapaSeccionNombreNumero = [
                'operaciones' => 2,
                'producto' => 4,
                'personal' => 5
            ];
            // Validar por código de pregunta, no por índice
            $mapaCodigos = [
                2 => array_map(function($n) { return sprintf('PREG_01_%02d', $n); }, $preguntasConImagen[2]), // Operaciones
                4 => array_map(function($n) { return sprintf('PREG_03_%02d', $n); }, $preguntasConImagen[4]), // Producto
                5 => array_map(function($n) { return sprintf('PREG_04_%02d', $n); }, $preguntasConImagen[5]), // Personal
            ];
            foreach ($data['secciones'] as $seccion) {
                $nombre = $seccion['nombre_seccion'] ?? '';
                $numSeccion = $mapaSeccionNombreNumero[$nombre] ?? null;
                if (!$numSeccion || !isset($preguntasConImagen[$numSeccion])) continue;
                $codigosValidar = $mapaCodigos[$numSeccion] ?? [];
                foreach ($seccion['preguntas'] as $pregunta) {
                    $codigo = $pregunta['codigo_pregunta'] ?? '';
                    if (in_array($codigo, $codigosValidar)) {
                        if (empty($pregunta['imagenes']) || count($pregunta['imagenes']) < 1) {
                            return response()->json([
                                'error' => 'Debes subir al menos una imagen en la pregunta ' . $codigo . ' de la sección ' . $numSeccion
                            ], 422);
                        }
                    }
                }
            }

            // === PLANES DE ACCIÓN ===
            $data['planes'] = $request->input('planes', []);

            // === KPIs (si vienen como array ya formateado) ===
            $data['kpis'] = $request->input('kpis', []);

            // === VERIFICACIÓN DE URL DE IMÁGENES (opcional) ===
            foreach ($data['secciones'] as &$seccion) {
                foreach ($seccion['preguntas'] as &$pregunta) {
                    // Asegurarse que sea arreglo, aunque venga vacío
                    if (!isset($pregunta['imagenes']) || !is_array($pregunta['imagenes'])) {
                        $pregunta['imagenes'] = [];
                    }

                    // Filtrar y asegurar máximo 5 URLs válidas
                    $pregunta['imagenes'] = collect($pregunta['imagenes'])
                        ->filter(fn($url) => is_string($url) && str_starts_with($url, 'http'))
                        ->take(5)
                        ->values()
                        ->all();
                }
            }

            // === LOG ===
            Log::info("✅ Estructura final lista para insertar:", $data);

            // === INSERTAR EN BIGQUERY ===
            $table = $this->bigQuery
                ->dataset(config('admin.bigquery.dataset'))
                ->table(config('admin.bigquery.visitas_table'));

            $insertResponse = $table->insertRows([['data' => $data]]);

            if ($insertResponse->isSuccessful()) {
                session()->forget('uploaded_images');

                try {
                    $correoUsuario = $data['correo_realizo'];

                    // === CONSULTAR correo tienda desde BigQuery ===
                    $queryTienda = $this->bigQuery->query(<<<'SQL'
                    WITH emails AS (
                      SELECT ADDRNUMBER, ANY_VALUE(SMTP_ADDR) AS SMTP_ADDR
                      FROM `adoc-bi-prd`.`SAP_ECC`.`ADR6`
                      WHERE SMTP_ADDR IS NOT NULL AND SMTP_ADDR != ''
                      GROUP BY ADDRNUMBER
                    )
SELECT
  w.LAND1 AS Pais,
  CONCAT(w.LAND1, COALESCE(a.SORT1,'')) AS Pais_Tienda,
  e.SMTP_ADDR AS Email
FROM `adoc-bi-prd`.`SAP_ECC`.`T001W` AS w
JOIN `adoc-bi-prd`.`SAP_ECC`.`KNA1` AS c ON c.KUNNR = w.KUNNR
LEFT JOIN `adoc-bi-prd`.`SAP_ECC`.`ADRC` AS a ON a.ADDRNUMBER = c.ADRNR
LEFT JOIN emails AS e ON e.ADDRNUMBER = a.ADDRNUMBER
WHERE
  w.LAND1 IN ('SV','GT','HN','CR','NI','PA')
  AND w.VLFKZ = 'A'
  AND UPPER(a.SORT1) NOT IN ('WHS','BT1')
  AND CONCAT(w.LAND1, COALESCE(a.SORT1,'')) = @tienda
  AND w.LAND1 = @pais
LIMIT 1
SQL
                    )->parameters([
                      'tienda' => $crmIdTiendaCompleto, // ej: "SV1234"
                      'pais'   => $data['pais']         // ej: "SV"
                    ]);


                    Log::info('➡️ Consultando correo tienda...');
                    Log::info("🔎 Buscando correo de tienda con:", [
                        'pais_tienda' => $crmIdTiendaCompleto,
                        'pais' => $data['pais']
                    ]);

                    $resultTienda = $this->bigQuery->runQuery($queryTienda);
                    Log::info('✅ Consulta correo tienda completada');

                    $correoTienda = null;
                    foreach ($resultTienda->rows() as $row) {
                        Log::info("📥 Resultado de correo tienda:", (array) $row);
                        if (!empty($row['Email'])) {
                            $correoTienda = $row['Email'];
                            break;
                        }
                        if (!empty($row['email'])) {
                            $correoTienda = $row['email'];
                            break;
                        }
                    }

                    // === CONSULTAR correo jefe desde BigQuery ===
                    $queryJefe = $this->bigQuery->query(
                        'SELECT CORREO_GERENTE FROM `adoc-bi-dev.DEV_OPB.dim_gerentes`
                        WHERE BV_PAIS = @bv_pais'
                    )->parameters([
                        'bv_pais' => $data['pais']
                    ]);

                    Log::info('➡️ Consultando correo jefe...');
                    Log::info("🔎 Buscando correo de jefe con:", [
                        'BV_PAIS' => $data['pais'] // Usa el mismo prefijo que formaste para CRM_ID_TIENDA (SV, GT, etc.)
                    ]);
                    $resultJefe = $this->bigQuery->runQuery($queryJefe);
                    Log::info('✅ Consulta correo jefe completada');

                    $correoJefe = null;
                    foreach ($resultJefe->rows() as $row) {
                        Log::info("📥 Resultado de correo jefe:", (array) $row);
                        $correoJefe = $row['CORREO_GERENTE'] ?? null;
                    }

                    // === CALCULAR PUNTUACIONES ===
                    $promediosPorArea = EvaluacionHelper::calcularPromediosPorArea($data['secciones'], $data['kpis']);
                    $totales = EvaluacionHelper::calcularTotalPonderado($promediosPorArea);

                    $data['resumen_areas'] = [];
                    foreach ($promediosPorArea as $area => $info) {
                        $data['resumen_areas'][] = [
                            'nombre' => ucfirst($area),
                            'puntos' => $info['promedio'] ?? 'N/A',
                            'estrellas' => isset($info['promedio']) ? intval(round($info['promedio'] / 0.2)) : 'N/A',
                        ];
                    }

                    $data['puntos_totales'] = $totales['puntaje'];
                    $data['estrellas'] = $totales['estrellas'];

                   // === ENVIAR CORREO ===
                    $destinatariosCC = array_filter([$correoTienda, $correoJefe]);
                    
                    if ($correoOriginal) {
                        try {
                            // Renderizar el contenido del correo como HTML
                            Log::info('➡️ Generando HTML...');
                            $html = view('emails.visita_confirmacion', ['datos' => $data])->render();
                    
                            // Insertar comentario con los correos justo después del <body>
                            $comentarioCorreos = "<!--\n";
                            $comentarioCorreos .= "correo_realizo: {$correoOriginal}\n";
                            $comentarioCorreos .= "correo_tienda: ".($correoTienda ?? 'NO ENCONTRADO')."\n";
                            $comentarioCorreos .= "correo_jefe_zona: {$correoJefe}\n";
                            $comentarioCorreos .= "-->\n";
                    
                            $html = preg_replace_callback('/<body[^>]*>/', function ($matches) use ($comentarioCorreos) {
                                return $matches[0] . "\n" . $comentarioCorreos;
                            }, $html);

                            Log::info('✅ HTML generado con comentario');
                    
                            // Crear nombre del archivo único
                            $nombreArchivo = 'visita_' . Str::random(8) . '.html';
                    
                            // 📁 Carpeta completa en CPanel (public_html/retail/correos)
                            $rutaCarpeta = $_SERVER['DOCUMENT_ROOT'] . '/retail/correos';
                    
                            if (!File::exists($rutaCarpeta)) {
                                File::makeDirectory($rutaCarpeta, 0755, true);
                            }
                    
                            // Ruta completa del archivo
                            $rutaArchivo = $rutaCarpeta . '/' . $nombreArchivo;
                    
                            // Guardar el archivo HTML
                            File::put($rutaArchivo, $html);
                    
                            // Generar URL pública para Power Automate
                            $urlHtml = url('retail/correos/' . $nombreArchivo);
                    
                            Log::info('✅ HTML de visita generado y guardado para Power Automate', [
                                'url' => $urlHtml
                            ]);
                        } catch (\Exception $e) {
                            Log::error('❌ Error al generar archivo HTML', [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('❌ Error al crear el correo de confirmación', [
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Formulario guardado correctamente y HTML generado',
                    'url_html' => $urlHtml ?? null
                ]);
            } else {
                Log::error('❌ Error al insertar en BigQuery', [
                    'errores' => $insertResponse->failedRows()
                ]);
                return response()->json(['error' => 'Error al insertar en BigQuery.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error interno al guardar sección', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 SUBIDA OPTIMIZADA SIN INTERVENTION IMAGE (solo PHP nativo)
     */
    private function uploadImageToCloudStorageOptimized($file, $nombreCampo, $prefix = 'observaciones/')
    {
        try {
            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }
            $tokenUnico = session('token_unico');

            // 📏 Log tamaño original
            $originalSize = $file->getSize() / (1024 * 1024);
            Log::info("🔍 Procesando imagen con PHP nativo", [
                'campo' => $nombreCampo,
                'tamaño_original' => round($originalSize, 2) . 'MB'
            ]);

            // 🎨 COMPRESIÓN CON GD (PHP nativo)
            $tempPath = $file->getRealPath();
            $imageInfo = getimagesize($tempPath);

            if (!$imageInfo) {
                throw new \Exception("No se pudo leer la información de la imagen");
            }

            // Crear imagen desde archivo según tipo
            switch ($imageInfo['mime']) {
                case 'image/jpeg':
                    $sourceImage = \imagecreatefromjpeg($tempPath);
                    break;
                case 'image/png':
                    $sourceImage = \imagecreatefrompng($tempPath);
                    break;
                case 'image/gif':
                    $sourceImage = \imagecreatefromgif($tempPath);
                    break;
                case 'image/webp':
                    $sourceImage = \imagecreatefromwebp($tempPath);
                    break;
                default:
                    throw new \Exception("Tipo de imagen no soportado: " . $imageInfo['mime']);
            }

            if (!$sourceImage) {
                throw new \Exception("No se pudo crear la imagen desde el archivo");
            }

            // Obtener dimensiones originales
            $originalWidth = imagesx($sourceImage);
            $originalHeight = imagesy($sourceImage);

            // Calcular nuevas dimensiones (máximo 800px)
            $maxDimension = 800;
            if ($originalWidth > $maxDimension || $originalHeight > $maxDimension) {
                if ($originalWidth > $originalHeight) {
                    $newWidth = $maxDimension;
                    $newHeight = ($originalHeight * $maxDimension) / $originalWidth;
                } else {
                    $newHeight = $maxDimension;
                    $newWidth = ($originalWidth * $maxDimension) / $originalHeight;
                }
            } else {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            }

            // Crear nueva imagen redimensionada
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Mantener transparencia para PNG
            if ($imageInfo['mime'] === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefill($resizedImage, 0, 0, $transparent);
            }

            // Redimensionar imagen
            imagecopyresampled(
                $resizedImage,
                $sourceImage,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $originalWidth,
                $originalHeight
            );

            // 🗜️ COMPRIMIR ITERATIVAMENTE
            $quality = 85;
            $targetSizeBytes = 5 * 1024 * 1024; // 5MB target
            $attempts = 0;
            $maxAttempts = 8;

            do {
                // Capturar output de imagen comprimida
                ob_start();
                \imagejpeg($resizedImage, null, $quality);
                $imageData = ob_get_contents();
                ob_end_clean();

                $currentSize = strlen($imageData);

                Log::info("🔄 Intento compresión nativa", [
                    'intento' => $attempts + 1,
                    'calidad' => $quality,
                    'tamaño_actual' => round($currentSize / (1024 * 1024), 2) . 'MB'
                ]);

                if ($currentSize <= $targetSizeBytes) {
                    break; // ✅ Tamaño objetivo alcanzado
                }

                // Reducir calidad
                $quality = max(10, $quality - 15);
                $attempts++;
            } while ($attempts < $maxAttempts);

            // Limpiar memoria
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);

            $finalSizeMB = strlen($imageData) / (1024 * 1024);

            Log::info("📦 Compresión nativa finalizada", [
                'tamaño_final' => round($finalSizeMB, 2) . 'MB',
                'calidad_final' => $quality,
                'compresión' => round((1 - $finalSizeMB / $originalSize) * 100, 1) . '%'
            ]);

            // 🚨 VALIDACIÓN FINAL
            if ($finalSizeMB > 5.5) {
                throw new \Exception("Imagen aún muy grande: {$finalSizeMB}MB");
            }

            // 🔤 GENERAR NOMBRE ÚNICO
            $filename = sprintf(
                '%s%s_%s_%s_%s.jpg',
                $prefix,
                $nombreCampo,
                $tokenUnico,
                time(),
                substr(md5($imageData), 0, 8)
            );

            // ☁️ SUBIR A CLOUD STORAGE
            $this->bucket->upload($imageData, [
                'name' => $filename,
                'metadata' => [
                    'contentType' => 'image/jpeg',
                    'cacheControl' => 'public, max-age=3600',
                    'customMetadata' => [
                        'campo_formulario' => $nombreCampo,
                        'session_id' => $tokenUnico,
                        'tamaño_comprimido' => round($finalSizeMB, 2) . 'MB',
                        'fecha_subida' => now()->toISOString()
                    ]
                ]
            ]);

            $publicUrl = sprintf(
                'https://storage.cloud.google.com/%s/%s',
                config('services.google.storage_bucket'),
                $filename
            );

            Log::info("🎉 Imagen subida con PHP nativo", [
                'url' => $publicUrl,
                'tamaño_final' => round($finalSizeMB, 2) . 'MB'
            ]);

            return $publicUrl;
        } catch (\Exception $e) {
            Log::error('❌ Error en subida nativa', [
                'error' => $e->getMessage(),
                'campo' => $nombreCampo
            ]);
            return null;
        }
    }
}
