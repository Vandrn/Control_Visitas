<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Cloud\BigQuery\BigQueryClient;
use Carbon\Carbon;
use JsonMachine\JsonMachine;

class MigrarDatosBigQuery extends Command
{
    protected $signature = 'etl:migrar {--source=} {--batch=1000} {--memory=512}';
    protected $description = 'Migrar datos desde JSON a BigQuery con manejo eficiente de memoria';

    public function handle()
    {
        // Establecer límite de memoria
        $memoryLimit = $this->option('memory') . 'M';
        ini_set('memory_limit', $memoryLimit);
        $this->info("Límite de memoria establecido a: $memoryLimit");

        // Ruta del archivo JSON (configurable por parámetro o por defecto)
        $jsonPath = $this->option('source') ?? storage_path('app/datos.json');
        $batchSize = $this->option('batch');

        $this->info("Iniciando migración desde $jsonPath");

        if (!file_exists($jsonPath)) {
            $this->error('El archivo JSON no existe en la ruta: ' . $jsonPath);
            return 1;
        }

        // Comprobar tamaño de archivo
        $fileSize = filesize($jsonPath);
        $fileSizeMB = round($fileSize / (1024 * 1024), 2);
        $this->info("Tamaño del archivo: $fileSizeMB MB");

        try {

            $keyFilePath = storage_path('app/claves/adoc-bi-dev-debcb06854ae.json');

            $this->info("Looking for key file at: $keyFilePath");
            if (!file_exists($keyFilePath)) {
                $this->error("Key file does not exist at: $keyFilePath");
                return 1;
            }

            // Now use this absolute path for the BigQueryClient
            $bigQuery = new BigQueryClient([
                'projectId' => env('BIGQUERY_PROJECT_ID'),
                'keyFilePath' => $keyFilePath
            ]);

            $dataset = $bigQuery->dataset(env('BIGQUERY_DATASET'));
            $table = $dataset->table(env('BIGQUERY_TABLE'));

            // Procesar archivo JSON en streaming (requiere JsonMachine)
            $this->info('Procesando JSON en modo streaming...');

            // Verificar formato del JSON
            $firstChar = file_get_contents($jsonPath, false, null, 0, 1);
            if ($firstChar !== '[') {
                $this->error('El archivo JSON debe comenzar con un array "[". Formato detectado incorrecto.');
                return 1;
            }

            // Usar un enfoque por líneas para JSON simples (una alternativa si JsonMachine no está disponible)
            $this->processJsonByChunks($jsonPath, $table, $batchSize);

            $this->info("Migración completada con éxito.");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error durante la migración: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Procesa el JSON por fragmentos para un uso eficiente de memoria
     */
    private function processJsonByChunks($jsonPath, $table, $batchSize)
    {
        $handle = fopen($jsonPath, 'r');
        if (!$handle) {
            throw new \Exception('No se pudo abrir el archivo JSON');
        }

        $buffer = '';
        $depth = 0;
        $inString = false;
        $escape = false;
        $records = [];
        $recordCount = 0;
        $totalInserted = 0;
        $totalErrors = 0;
        $batchNumber = 1;

        $this->info('Comenzando procesamiento de registros...');
        $this->warn('Este proceso puede tardar varios minutos para archivos grandes.');

        // Leer primera línea (debe ser '[')
        $char = fgetc($handle);
        if ($char !== '[') {
            fclose($handle);
            throw new \Exception('Formato JSON inválido, debe comenzar con "["');
        }

        // Procesar caracter por caracter
        while (!feof($handle)) {
            $char = fgetc($handle);

            // Manejar secuencias de escape dentro de strings
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($char === '\\') {
                    $escape = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                $buffer .= $char;
                continue;
            }

            // Detectar strings
            if ($char === '"') {
                $inString = true;
                $buffer .= $char;
                continue;
            }

            // Controlar nivel de anidamiento
            if ($char === '{') {
                $depth++;
                $buffer .= $char;
            } elseif ($char === '}') {
                $depth--;
                $buffer .= $char;

                // Fin de un objeto JSON completo
                if ($depth === 0) {
                    $jsonObj = json_decode($buffer, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $records[] = $this->transformRecord($jsonObj);
                        $recordCount++;
                        $this->output->write("\rRegistros procesados: $recordCount");

                        // Procesar lote cuando se alcanza el tamaño deseado
                        if (count($records) >= $batchSize) {
                            list($inserted, $errors) = $this->insertBatch($table, $records, $batchNumber);
                            $totalInserted += $inserted;
                            $totalErrors += $errors;
                            $records = [];
                            $batchNumber++;
                            // Liberar memoria
                            gc_collect_cycles();
                        }
                    } else {
                        $this->warn("\nError decodificando JSON: " . json_last_error_msg());
                    }
                    $buffer = '';
                }
            } elseif ($depth > 0) {
                // Continuar construyendo el buffer solo si estamos dentro de un objeto
                $buffer .= $char;
            }

            // Ignorar comas entre objetos y otros caracteres fuera de objetos
        }

        fclose($handle);

        // Insertar registros restantes
        if (!empty($records)) {
            list($inserted, $errors) = $this->insertBatch($table, $records, $batchNumber);
            $totalInserted += $inserted;
            $totalErrors += $errors;
        }

        $this->newLine();
        $this->info("Proceso finalizado. Total de registros procesados: $recordCount");
        $this->info("Registros insertados: $totalInserted");

        if ($totalErrors > 0) {
            $this->warn("Registros con errores: $totalErrors");
        }
    }

    /**
     * Inserta un lote de registros en BigQuery
     * 
     * @return array [insertados, errores]
     */
    private function insertBatch($table, $records, $batchNumber)
    {
        $this->newLine();
        $this->info("Insertando lote #$batchNumber con " . count($records) . " registros...");

        // Formatear correctamente las filas con la clave 'data'
        $formattedRows = [];
        foreach ($records as $record) {
            $formattedRows[] = ['data' => $record];
        }

        $insertResponse = $table->insertRows($formattedRows);

        if ($insertResponse->isSuccessful()) {
            $this->info("Lote #$batchNumber insertado correctamente.");
            return [count($records), 0];
        } else {
            $failedRows = $insertResponse->failedRows();
            $errorCount = count($failedRows);
            $this->warn("Lote #$batchNumber: $errorCount errores de " . count($records) . " registros.");

            // Registrar sólo los primeros 5 errores para no saturar la consola
            $logged = 0;
            foreach ($failedRows as $rowIndex => $row) {
                if ($logged >= 5) {
                    $this->warn("... y " . ($errorCount - 5) . " errores más.");
                    break;
                }
                $this->warn("Fila $rowIndex: " . json_encode($row['info']['errors']));
                $logged++;
            }

            return [count($records) - $errorCount, $errorCount];
        }
    }

    /**
     * Transforma un registro para el formato BigQuery
     */
    private function transformRecord(array $item): array
    {
        // Convertir fechas a formato timestamp de BigQuery
        $fechaInicio = $this->parseDate($item['FECHA_HORA_INICIO'] ?? null);
        $fechaFin = $this->parseDate($item['FECHA_HORA_FIN'] ?? null);

        // Convertir fechas de plan a formato DATE de BigQuery
        $fechaPlan01 = $this->parseDate($item['FECHA_PLAN_01'] ?? null, 'Y-m-d');
        $fechaPlan02 = $this->parseDate($item['FECHA_PLAN_02'] ?? null, 'Y-m-d');
        $fechaPlan03 = $this->parseDate($item['FECHA_PLAN_03'] ?? null, 'Y-m-d');
        $fechaPlan04 = $this->parseDate($item['FECHA_PLAN_04'] ?? null, 'Y-m-d');
        $fechaPlan05 = $this->parseDate($item['FECHA_PLAN_05'] ?? null, 'Y-m-d');
        $fechaPlanAdic = $this->parseDate($item['FECHA_PLAN_ADIC'] ?? null, 'Y-m-d');

        // Mapeo completo de campos según el esquema
        return [
            'id' => (string)($item['ID_VISITA'] ?? ''),
            'session_id' => (string)($item['SESSION_ID'] ?? ''),
            'FECHA_HORA_INICIO' => $fechaInicio,
            'FECHA_HORA_FIN' => $fechaFin,
            'CORREO_REALIZO' => (string)($item['CORREO_REALIZO'] ?? ''),
            'LIDER_ZONA' => (string)($item['NOMBRE_REALIZO'] ?? ''),
            'PAIS' => (string)($item['PAIS_VISITA'] ?? ''),
            'ZONA' => (string)($item['ZONA_TIENDA'] ?? ''),
            'TIENDA' => (string)($item['TIENDA_EVALUAR'] ?? ''),
            'UBICACION' => (string)($item['UBICACION'] ?? ''),

            // Preguntas sección 01
            'PREG_01_01' => (string)($item['PREG_01_01'] ?? ''),
            'PREG_01_02' => (string)($item['PREG_01_02'] ?? ''),
            'PREG_01_03' => (string)($item['PREG_01_03'] ?? ''),
            'PREG_01_04' => (string)($item['PREG_01_04'] ?? ''),
            'PREG_01_05' => (string)($item['PREG_01_05'] ?? ''),
            'PREG_01_06' => (string)($item['PREG_01_06'] ?? ''),
            'PREG_01_07' => (string)($item['PREG_01_07'] ?? ''),
            'PREG_01_08' => (string)($item['PREG_01_08'] ?? ''),
            'PREG_01_09' => (string)($item['PREG_01_09'] ?? ''),
            'PREG_01_10' => (string)($item['PREG_01_10'] ?? ''),
            'PREG_01_11' => (string)($item['PREG_01_11'] ?? ''),
            'PREG_01_12' => (string)($item['PREG_01_12'] ?? ''),
            'PREG_01_13' => (string)($item['PREG_01_13'] ?? ''),
            'PREG_01_14' => (string)($item['PREG_01_14'] ?? ''),
            'OBS_01_01' => (string)($item['OBS_01_01'] ?? ''),

            // Preguntas sección 02
            'PREG_02_01' => (string)($item['PREG_02_01'] ?? ''),
            'PREG_02_02' => (string)($item['PREG_02_02'] ?? ''),
            'PREG_02_03' => (string)($item['PREG_02_03'] ?? ''),
            'PREG_02_04' => (string)($item['PREG_02_04'] ?? ''),
            'PREG_02_05' => (string)($item['PREG_02_05'] ?? ''),
            'PREG_02_06' => (string)($item['PREG_02_06'] ?? ''),
            'PREG_02_07' => (string)($item['PREG_02_07'] ?? ''),
            'PREG_02_08' => (string)($item['PREG_02_08'] ?? ''),
            'OBS_02_01' => (string)($item['OBS_02_01'] ?? ''),

            // Preguntas sección 03
            'PREG_03_01' => (string)($item['PREG_03_01'] ?? ''),
            'PREG_03_02' => (string)($item['PREG_03_02'] ?? ''),
            'PREG_03_03' => (string)($item['PREG_03_03'] ?? ''),
            'PREG_03_04' => (string)($item['PREG_03_04'] ?? ''),
            'PREG_03_05' => (string)($item['PREG_03_05'] ?? ''),
            'PREG_03_06' => (string)($item['PREG_03_06'] ?? ''),
            'PREG_03_07' => (string)($item['PREG_03_07'] ?? ''),
            'PREG_03_08' => (string)($item['PREG_03_08'] ?? ''),
            'OBS_03_01' => (string)($item['OBS_03_01'] ?? ''),

            // Preguntas sección 04
            'PREG_04_01' => (string)($item['PREG_04_01'] ?? ''),
            'PREG_04_02' => (string)($item['PREG_04_02'] ?? ''),
            'PREG_04_03' => (string)($item['PREG_04_03'] ?? ''),
            'PREG_04_04' => (string)($item['PREG_04_04'] ?? ''),
            'PREG_04_05' => (string)($item['PREG_04_05'] ?? ''),
            'PREG_04_06' => (string)($item['PREG_04_06'] ?? ''),
            'PREG_04_07' => (string)($item['PREG_04_07'] ?? ''),
            'PREG_04_08' => (string)($item['PREG_04_08'] ?? ''),
            'PREG_04_09' => (string)($item['PREG_04_09'] ?? ''),
            'PREG_04_10' => (string)($item['PREG_04_10'] ?? ''),
            'PREG_04_11' => (string)($item['PREG_04_11'] ?? ''),
            'PREG_04_12' => (string)($item['PREG_04_12'] ?? ''),
            'PREG_04_13' => (string)($item['PREG_04_13'] ?? ''),
            'PREG_04_14' => (string)($item['PREG_04_14'] ?? ''),
            'PREG_04_15' => (string)($item['PREG_04_15'] ?? ''),
            'OBS_04_01' => (string)($item['OBS_04_01'] ?? ''),

            // Preguntas sección 05
            'PREG_05_01' => (string)($item['PREG_05_01'] ?? ''),
            'PREG_05_02' => (string)($item['PREG_05_02'] ?? ''),
            'PREG_05_03' => (string)($item['PREG_05_03'] ?? ''),
            'PREG_05_04' => (string)($item['PREG_05_04'] ?? ''),
            'PREG_05_05' => (string)($item['PREG_05_05'] ?? ''),
            'PREG_05_06' => (string)($item['PREG_05_06'] ?? ''),
            'OBS_05_01' => (string)($item['OBS_05_01'] ?? ''),

            // Planes de acción
            'PLAN_01' => (string)($item['PLAN_01'] ?? ''),
            'FECHA_PLAN_01' => $fechaPlan01,
            'PLAN_02' => (string)($item['PLAN_02'] ?? ''),
            'FECHA_PLAN_02' => $fechaPlan02,
            'PLAN_03' => (string)($item['PLAN_03'] ?? ''),
            'FECHA_PLAN_03' => $fechaPlan03,
            'PLAN_04' => (string)($item['PLAN_04'] ?? ''),
            'FECHA_PLAN_04' => $fechaPlan04,
            'PLAN_05' => (string)($item['PLAN_05'] ?? ''),
            'FECHA_PLAN_05' => $fechaPlan05,
            'PLAN_ADIC' => (string)($item['PLAN_ADIC'] ?? ''),
            'FECHA_PLAN_ADIC' => $fechaPlanAdic,

            // Imágenes
            'IMG_OBS_OPE_URL' => (string)($item['IMG_OBS_OPE_URL'] ?? ''),
            'IMG_OBS_ADM_URL' => (string)($item['IMG_OBS_ADM_URL'] ?? ''),
            'IMG_OBS_PRO_URL' => (string)($item['IMG_OBS_PRO_URL'] ?? ''),
            'IMG_OBS_PER_URL' => (string)($item['IMG_OBS_PER_URL'] ?? ''),
            'IMG_OBS_KPI_URL' => (string)($item['IMG_OBS_KPI_URL'] ?? ''),
        ];
    }

    /**
     * Parsea una fecha a formato BigQuery
     * 
     * @param string|null $dateString
     * @param string $format Formato de salida (TIMESTAMP o DATE)
     * @return string|null
     */
    private function parseDate($dateString, $format = 'Y-m-d\TH:i:s.u\Z')
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $date = Carbon::parse($dateString);
            return $date->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }
}
