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
            $zonas[] = $row['ZONA'];
        }

        return response()->json($zonas);
    }


    public function obtenerTiendas($bv_pais, $zona)
    {
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
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $tiendas = [];
        foreach ($results->rows() as $row) {
            $tiendas[] = [
                'TIENDA' => $row['TIENDA'],       // Solo NAME1, sin concatenación
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
            $data = [
                'id' => uniqid(),
                'session_id' => $formId,
                'fecha_hora_inicio' => $request->input('fecha_hora_inicio'),
                'fecha_hora_fin' => now(),
                'correo_realizo' => $request->input('correo_realizo'),
                'lider_zona' => $request->input('lider_zona'),
                'tienda' => $tiendaCompleta,
                'ubicacion' => $request->input('ubicacion'),
                'pais' => $request->input('pais'),
                'zona' => $request->input('zona'),
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
                    $queryTienda = $this->bigQuery->query(
                        'SELECT correo FROM `adoc-bi-dev.OPB.crm_store_email`
                                WHERE pais_tienda = @tienda AND pais = @pais'
                    )->parameters([
                        'tienda' => $crmIdTiendaCompleto,
                        'pais' => $nombrePais // Usa el nombre completo del país
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
                        $correoTienda = $row['correo'] ?? $row['CORREO'] ?? null;
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

                    if ($correoUsuario) {
                        try {
                            Log::info('➡️ Generando HTML...');

                            // Renderizar Blade como HTML
                            $html = view('emails.visita_confirmacion', ['datos' => $data])->render();

                            // Insertar comentarios HTML con correos justo después del <body>
                            $comentarioCorreos = "<!--\n";
                            $comentarioCorreos .= "correo_realizo: {$correoUsuario}\n";
                            $comentarioCorreos .= "correo_tienda: {$correoTienda}\n";
                            $comentarioCorreos .= "correo_jefe_zona: {$correoJefe}\n";
                            $comentarioCorreos .= "-->\n";

                            // Agregar el comentario justo después del <body>
                            $html = preg_replace('/<body[^>]*>/', '$0' . "\n" . $comentarioCorreos, $html);

                            Log::info('✅ HTML generado correctamente');

                            // Crear nombre del archivo único
                            $nombreArchivo = 'visita_' . Str::random(8) . '.html';

                            // Crear carpeta si no existe
                            $rutaCarpeta = public_path('correos');
                            if (!File::exists($rutaCarpeta)) {
                                File::makeDirectory($rutaCarpeta, 0755, true);
                            }

                            // Guardar HTML en el archivo
                            $rutaArchivo = $rutaCarpeta . '/' . $nombreArchivo;
                            Log::info('➡️ Guardando archivo HTML...');
                            File::put($rutaArchivo, $html);
                            Log::info('✅ Archivo HTML guardado');

                            // URL pública para usarla en Power Automate
                            $urlHtml = url('correos/' . $nombreArchivo);

                            Log::info('✅ HTML de visita generado y guardado para Power Automate', [
                                'url' => $urlHtml
                            ]);
                        } catch (\Exception $e) {
                            Log::error('❌ Error al crear el HTML de confirmación', [
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
