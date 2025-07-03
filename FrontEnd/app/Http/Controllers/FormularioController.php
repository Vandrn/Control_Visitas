<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

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
        // Modificar la consulta para usar BigQuery
        $query = 'SELECT DISTINCT BV_PAIS, SK_PAIS FROM `adoc-bi-dev.OPB.DIM_PAIS`';
        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $paises = [];
        foreach ($results->rows() as $row) {
            $paises[] = [
                'label' => $row['BV_PAIS'],   // lo que se muestra
                'value' => $row['SK_PAIS'],   // lo que se usa para buscar zonas en dim_tienda
            ];
        }

        return response()->json($paises);
    }

    public function obtenerZonas($sk_pais)
    {
        $query = 'SELECT DISTINCT ZONA FROM `adoc-bi-dev.OPB.DIM_TIENDA` WHERE SK_PAIS = @sk_pais';
        $queryJobConfig = $this->bigQuery->query($query)->parameters([
            'sk_pais' => $sk_pais
        ]);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $zonas = [];
        foreach ($results->rows() as $row) {
            $zonas[] = $row['ZONA'];
        }

        return response()->json($zonas);
    }

    public function obtenerTiendas($sk_pais, $zona)
    {
        $query = 'SELECT dm.TIENDA, dm.UBICACION, a.GEO FROM `adoc-bi-dev.OPB.DIM_TIENDA` dm left join (select concat(dsm.LATITUD,",",replace(dsm.LONGITUD,"\'","")) GEO, dsm.PAIS_TIENDA from `adoc-bi-dev`.bi_lab.dim_store_master dsm where dsm.LATITUD not in (\'nan\')) a on dm.BV_PAIS_TIENDA = a.PAIS_TIENDA WHERE dm.SK_PAIS = @sk_pais AND dm.ZONA = @zona';
        $queryJobConfig = $this->bigQuery->query($query)->parameters([
            'sk_pais' => $sk_pais,
            'zona' => $zona
        ]);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $tiendas = [];
        foreach ($results->rows() as $row) {
            $tiendas[] = [
                'TIENDA' => $row['TIENDA'],
                'UBICACION' => $row['UBICACION'],
                'GEO' => $row['GEO'] ?? null  // Coordenadas: "lat,lng"
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
            if (!session()->has('token_unico')) {
                session(['token_unico' => Str::uuid()->toString()]);
            }
            $formId = session('token_unico');

            // === DATOS BASE (orden según esquema) ===
            $data = [
                'id' => uniqid(),
                'session_id' => $formId,
                'fecha_hora_inicio' => $request->input('fecha_hora_inicio'),
                'fecha_hora_fin' => now(),
                'correo_realizo' => $request->input('correo_realizo'),
                'lider_zona' => $request->input('lider_zona'),
                'tienda' => $request->input('tienda'),
                'ubicacion' => $request->input('ubicacion'),
                'pais' => $request->input('pais'),
                'zona' => $request->input('zona'),
            ];

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
                ->dataset(env('BIGQUERY_DATASET'))
                ->table(env('BIGQUERY_TABLE'));

            $insertResponse = $table->insertRows([['data' => $data]]);

            if ($insertResponse->isSuccessful()) {
                session()->forget('uploaded_images'); // limpiar sesión de imágenes
                return response()->json(['success' => true, 'message' => 'Formulario guardado correctamente']);
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
