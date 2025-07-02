<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            'keyFilePath' => storage_path('app' . config('services.google.keyfile')),
        ]);

        // Initialize Google Cloud Storage client
        $this->storage = new StorageClient([
            'projectId' => config('services.google.project_id'),
            'keyFilePath' => storage_path('app' . config('services.google.keyfile')),
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
        $query = 'SELECT * FROM `adoc-bi-dev.OPB.gerente_retail` WHERE session_id = @session_id';
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
        $query = 'SELECT DISTINCT PAIS FROM `adoc-bi-dev.OPB.crm_stores`';
        $queryJobConfig = $this->bigQuery->query($query);
        $paises = $this->bigQuery->runQuery($queryJobConfig);

        // Convertir los resultados a un array
        $paisesArray = [];
        foreach ($paises->rows() as $row) {
            $paisesArray[] = $row['PAIS'];
        }

        return response()->json($paisesArray);
    }

    public function obtenerZonas($pais)
    {
        // Modificar la consulta para usar BigQuery
        $query = 'SELECT DISTINCT ZONA FROM `adoc-bi-dev.OPB.crm_stores` WHERE PAIS = @pais';
        $queryJobConfig = $this->bigQuery->query($query)->parameters(['pais' => $pais]);
        $zonas = $this->bigQuery->runQuery($queryJobConfig);

        // Convertir los resultados a un array
        $zonasArray = [];
        foreach ($zonas->rows() as $row) {
            $zonasArray[] = $row['ZONA'];
        }

        return response()->json($zonasArray);
    }

    public function obtenerTiendas($pais, $zona)
    {
        // Modificar la consulta para usar BigQuery
        $query = 'SELECT CRM_ID_TIENDA, PAIS_TIENDA, UBICACION FROM `adoc-bi-dev.OPB.crm_stores` WHERE PAIS = @pais AND ZONA = @zona';
        $queryJobConfig = $this->bigQuery->query($query)->parameters(['pais' => $pais, 'zona' => $zona]);
        $tiendas = $this->bigQuery->runQuery($queryJobConfig);

        // Convertir los resultados a un array
        $tiendasArray = [];
        foreach ($tiendas->rows() as $row) {
            $tiendasArray[] = [
                'CRM_ID_TIENDA' => $row['CRM_ID_TIENDA'],
                'PAIS_TIENDA' => $row['PAIS_TIENDA'],
                'UBICACION' => $row['UBICACION']
            ];
        }

        return response()->json($tiendasArray);
    }

    public function guardarSeccion(Request $request)
    {
        try {
            if (!session()->has('token_unico')) {
                session(['token_unico' => \Illuminate\Support\Str::uuid()->toString()]);
            }
            $formId = session('token_unico');


            // Datos base
            $data = [
                'id' => uniqid(),
                'session_id' => $formId,
                'FECHA_HORA_INICIO' => $request->input('FECHA_HORA_INICIO'),
                'FECHA_HORA_FIN' => now(),
                'CORREO_REALIZO' => $request->correo_tienda ?? null,
                'LIDER_ZONA' => $request->jefe_zona ?? null,
                'PAIS' => $request->pais ?? null,
                'ZONA' => $request->zona ?? null,
                'TIENDA' => $request->tienda ?? null,
                'UBICACION' => $request->ubicacion ?? null,
            ];

            // Recolectar campos del formulario
            foreach ($request->all() as $key => $value) {
                if (
                    strpos($key, 'PREG_') !== false ||
                    strpos($key, 'OBS_') !== false ||
                    strpos($key, 'PLAN_') !== false
                ) {
                    $data[$key] = $value;
                }

                if (strpos($key, 'FECHA_PLAN_') !== false) {
                    $data[$key] = (!empty($value) && strtotime($value)) ? $value : null;
                }
            }

            // Manejo de imágenes
            $imageFields = [
                'IMG_OBS_OPE' => 'IMG_OBS_OPE_URL',
                'IMG_OBS_ADM' => 'IMG_OBS_ADM_URL',
                'IMG_OBS_PRO' => 'IMG_OBS_PRO_URL',
                'IMG_OBS_PER' => 'IMG_OBS_PER_URL',
                'IMG_OBS_KPI' => 'IMG_OBS_KPI_URL'
            ];

            foreach ($imageFields as $fileField => $urlField) {
                if ($request->hasFile($fileField)) {
                    $file = $request->file($fileField);
                    Log::info("Intentando subir $fileField...");
                    $publicUrl = $this->uploadImageToCloudStorage($file, $fileField);
                    Log::info("Resultado URL $fileField:", [$publicUrl]);
                    if ($publicUrl) {
                        $data[$fileField] = $file->getClientOriginalName();
                        $data[$urlField] = $publicUrl;
                    } else {
                        Log::warning("No se pudo subir la imagen para: $fileField");
                    }
                }
            }

            Log::info('Data being inserted into BigQuery:', $data);

            // Insertar en BigQuery
            $table = $this->bigQuery->dataset(env('BIGQUERY_DATASET'))->table(env('BIGQUERY_TABLE'));
            $insertResponse = $table->insertRows([['data' => $data]]);

            if ($insertResponse->isSuccessful()) {
                return response()->json(['message' => 'Formulario guardado correctamente.']);
            } else {
                Log::error('Error al insertar datos en BigQuery:', ['errors' => $insertResponse->failedRows()]);
                return response()->json(['error' => 'Error al insertar datos en BigQuery.'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error al guardar la sección:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno en el servidor: ' . $e->getMessage()], 500);
        }
    }

    private function uploadImageToCloudStorage($file, $nombreCampo, $prefix = 'observaciones/')
    {
        try {
            if (!session()->has('token_unico')) {
                session(['token_unico' => \Illuminate\Support\Str::uuid()->toString()]);
            }
            $tokenUnico = session('token_unico');

            $extension = $file->getClientOriginalExtension();

            $filename = sprintf(
                '%s%s_%s.%s',
                $prefix,
                $nombreCampo,
                $tokenUnico,
                $extension
            );

            $storage = new \Google\Cloud\Storage\StorageClient([
                'keyFilePath' => base_path(config('services.google.keyfile'))
            ]);

            $bucket = $storage->bucket(config('services.google.storage_bucket'));


            $fileContents = file_get_contents($file->getRealPath());
            $bucket->upload($fileContents, [
                'name' => $filename,
                'metadata' => [
                    'contentType' => $file->getMimeType(),
                    'cacheControl' => 'public, max-age=3600'
                ],
                'predefinedAcl' => 'public-read'
            ]);

            $publicUrl = sprintf(
                'https://storage.cloud.google.com/%s/%s',
                config('services.google.storage_bucket'),
                $filename
            );

            return $publicUrl;
        } catch (\Exception $e) {
            Log::error('Cloud Storage Upload Error: ' . $e->getMessage());
            Log::error("Falló subida para $nombreCampo: " . $e->getMessage());
            return null;
        }
    }
}
