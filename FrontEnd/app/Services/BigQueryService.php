<?php

namespace App\Services;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;
use App\Services\FormProcessingService;

class BigQueryService
{
    private FormProcessingService $formProcessing;
    protected $bigQuery;
    protected $dataset;
    protected $table;

    public function __construct(FormProcessingService $formProcessing)
    {
        $this->formProcessing = $formProcessing;
        $this->bigQuery = new BigQueryClient([
            'projectId' => config('services.google.project_id'),
            'keyFilePath' => storage_path('app/' . config('services.google.keyfile')),
        ]);

        $this->dataset = config('admin.bigquery.dataset');
        $this->table = config('admin.bigquery.visitas_table');
    }


    /**
     * OBTENER PROGRESO - Endpoint para obtener pantalla actual y progreso guardado
    */
    public function getProgreso($sessionId)
    {
        $query = sprintf(
            "SELECT pantalla_actual FROM `%s.%s.%s`
            WHERE session_id = @session_id
            LIMIT 1",
            config('services.google.project_id'),
            $this->dataset,
            $this->table
        );

        $job = $this->bigQuery->query($query)
            ->parameters(['session_id' => $sessionId])
            ->useLegacySql(false)
            ->location('US');

        $results = $this->bigQuery->runQuery($job);

        foreach ($results as $row) {
            return ['pantalla_actual' => $row['pantalla_actual'] ?? null];
        }

        return [];
    }


    /**
     * GUARDAR PROGRESO - Endpoint para actualizar pantalla actual
     */
    public function updateProgreso($sessionId, $pantalla)
    {
        $query = sprintf(
            "MERGE `%s.%s.%s` T
            USING (SELECT @session_id AS session_id, @pantalla AS pantalla_actual) S
            ON T.session_id = S.session_id
            WHEN MATCHED THEN
            UPDATE SET pantalla_actual = S.pantalla_actual, updated_at = CURRENT_TIMESTAMP()
            WHEN NOT MATCHED THEN
            INSERT (session_id, pantalla_actual, updated_at)
            VALUES (S.session_id, S.pantalla_actual, CURRENT_TIMESTAMP())",
            config('services.google.project_id'),
            $this->dataset,
            $this->table
        );

        $job = $this->bigQuery->query($query)
            ->parameters([
                'session_id' => $sessionId,
                'pantalla' => $pantalla,
            ])
            ->useLegacySql(false)
            ->location('US');

        $this->bigQuery->runQuery($job);
    }

    /**
     * 🛡️ Helper para escapear strings en SQL y manejo de NULL
     */
    protected function escapeString($value)
    {
        if ($value === null || $value === '' || $value === false) {
            return 'NULL';
        }
        return "'" . addslashes($value) . "'";
    }

    /**
     * 🆕 INSERT INICIAL - Crear registro con session_id único
     * Se ejecuta en la vista "datos"
     * ✅ IMPORTANTE: Usa Query Job (DML) en lugar de streaming insert para evitar buffer lock
     */
    public function crearFormulario($datos)
    {
        try {
            // Generar session_id único
            $sessionId = Str::uuid()->toString();
            $recordId = Str::uuid()->toString();

            // ✅ Solo campos que existen en el esquema BQ
            $fechaInicio = $datos['fecha_hora_inicio'] ?? now()->toIso8601String();

            // Construir INSERT como Query Job evitando parámetros NULL
            // Los campos opcionales se ponen directamente en la SQL si no existen
            $insertQuery = sprintf(
                'INSERT INTO `%s.%s.%s` 
                (id, session_id, fecha_hora_inicio, fecha_hora_fin, correo_realizo, lider_zona, tienda, ubicacion, pais, zona, modalidad, secciones, kpis, planes)
                VALUES 
                (%s, %s, %s, NULL, %s, %s, %s, %s, %s, %s, %s, ARRAY[], ARRAY[], ARRAY[])',
                config('services.google.project_id'),
                $this->dataset,
                $this->table,
                $this->escapeString($recordId),
                $this->escapeString($sessionId),
                $this->escapeString($fechaInicio),
                $this->escapeString($datos['correo_realizo'] ?? null),
                $this->escapeString($datos['lider_zona'] ?? null),
                $this->escapeString($datos['tienda'] ?? null),
                $this->escapeString($datos['ubicacion'] ?? null),
                $this->escapeString($datos['pais'] ?? null),
                $this->escapeString($datos['zona'] ?? null),
                $this->escapeString($datos['modalidad_visita'] ?? null)
            );

            Log::info('📝 Query INSERT construida (sin parámetros NULL)', [
                'session_id' => $sessionId
            ]);

            $queryJobConfig = $this->bigQuery->query($insertQuery)
                ->useLegacySql(false)
                ->location('US');

            $insertJob = $this->bigQuery->runQuery($queryJobConfig);
            $insertJob->waitUntilComplete();

            if ($insertJob->isComplete()) {
                Log::info('✅ Registro inicial creado en BigQuery (Query Job, no streaming)', [
                    'session_id' => $sessionId,
                    'correo' => $datos['correo_realizo'],
                    'tabla' => $this->table,
                    'metodo' => 'INSERT Query Job (evita streaming buffer)'
                ]);

                return [
                    'success' => true,
                    'session_id' => $sessionId,
                    'message' => 'Registro creado exitosamente'
                ];
            } else {
                throw new \Exception('INSERT Query Job no se completó');
            }
        } catch (\Exception $e) {
            Log::error('❌ Excepción en crearFormulario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🔄 UPDATE - Actualizar sección específica
     * Se ejecuta después de cada vista de preguntas
     * Estrategia: MERGE para evitar streaming buffer issues
     */
    public function actualizarSeccion($sessionId, $nombreSeccion, $preguntas)
    {
        try {
            if (!$sessionId) {
                throw new \Exception('session_id no proporcionado');
            }

            // ✅ Transformar campo 'valor' a 'respuesta' para que coincida con schema BQ
            $preguntasFormateadas = array_map(function($preg) {
                return [
                    'codigo_pregunta' => $preg['codigo_pregunta'] ?? null,
                    'respuesta' => $preg['valor'] ?? $preg['respuesta'] ?? null,
                    'imagenes' => $preg['imagenes'] ?? []
                ];
            }, $preguntas);

            Log::info('📝 Actualizando sección en BigQuery', [
                'session_id' => $sessionId,
                'seccion' => $nombreSeccion,
                'preguntas_count' => count($preguntasFormateadas)
            ]);

            // PASO 1: Leer el registro actual para evitar streaming buffer issue
            $selectQuery = sprintf(
                'SELECT secciones FROM `%s.%s.%s` WHERE session_id = @session_id LIMIT 1',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            $selectJobConfig = $this->bigQuery->query($selectQuery)
                ->parameters(['session_id' => $sessionId])
                ->useLegacySql(false)
                ->location('US');

            $selectJob = $this->bigQuery->runQuery($selectJobConfig);
            $selectJob->waitUntilComplete();

            $seccionesActuales = [];
            foreach ($selectJob->rows() as $row) {
                $seccionesActuales = isset($row['secciones']) ? (array)$row['secciones'] : [];
                break;
            }

            // PASO 2: Construir nueva sección como JSON
            $nuevaSeccion = [
                'nombre_seccion' => $nombreSeccion,
                'preguntas' => $preguntasFormateadas
            ];

            // PASO 3: Construir array de todas las secciones (existentes + nueva)
            $allSecciones = [];
            
            // Preservar secciones antiguas (excepto la que se actualiza)
            foreach ($seccionesActuales as $sec) {
                if ($sec['nombre_seccion'] !== $nombreSeccion) {
                    $allSecciones[] = $sec;
                }
            }

            // Agregar la nueva sección
            $allSecciones[] = $nuevaSeccion;

            // PASO 4: Convertir a JSON para enviar como parámetro
            $seccionesJSON = json_encode($allSecciones);

            // PASO 5: Mover la lógica compleja al USING para evitar correlated subquery en UPDATE
            $mergeQuery = sprintf(
                'MERGE `%s.%s.%s` T
                USING (
                  SELECT 
                    @session_id as session_id,
                    ARRAY(
                      SELECT AS STRUCT
                        JSON_EXTRACT_SCALAR(seccion, "$.nombre_seccion") as nombre_seccion,
                        ARRAY(
                          SELECT AS STRUCT
                            JSON_EXTRACT_SCALAR(pregunta, "$.codigo_pregunta") as codigo_pregunta,
                            JSON_EXTRACT_SCALAR(pregunta, "$.respuesta") as respuesta,
                            ARRAY(SELECT JSON_EXTRACT_SCALAR(value, "$") FROM UNNEST(JSON_EXTRACT_ARRAY(pregunta, "$.imagenes")) as value) as imagenes
                          FROM UNNEST(JSON_EXTRACT_ARRAY(seccion, "$.preguntas")) as pregunta
                        ) as preguntas
                      FROM UNNEST(JSON_EXTRACT_ARRAY(PARSE_JSON(@secciones_json))) as seccion
                    ) as new_secciones
                ) S
                ON T.session_id = S.session_id
                WHEN MATCHED THEN
                  UPDATE SET secciones = S.new_secciones',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            Log::info('📋 MERGE query lista para ejecutar', [
                'session_id' => $sessionId,
                'nombre_seccion' => $nombreSeccion,
                'secciones_json' => $seccionesJSON
            ]);

            $mergeJobConfig = $this->bigQuery->query($mergeQuery)
                ->parameters(['session_id' => $sessionId, 'secciones_json' => $seccionesJSON])
                ->useLegacySql(false)
                ->location('US');

            $mergeJob = $this->bigQuery->runQuery($mergeJobConfig);
            $mergeJob->waitUntilComplete();

            if ($mergeJob->isComplete()) {
                Log::info('✅ Sección actualizada correctamente con MERGE', [
                    'session_id' => $sessionId,
                    'seccion' => $nombreSeccion
                ]);

                return [
                    'success' => true,
                    'session_id' => $sessionId,
                    'message' => 'Sección actualizada exitosamente'
                ];
            } else {
                throw new \Exception('MERGE query no se completó');
            }

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar sección', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'seccion' => $nombreSeccion,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 📖 OBTENER registro completo por session_id
     */
    public function obtenerRegistro($sessionId)
    {
        try {
            $query = sprintf(
                'SELECT * FROM `%s.%s.%s` WHERE session_id = @session_id LIMIT 1',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            $queryJobConfig = $this->bigQuery->query($query)
                ->parameters(['session_id' => $sessionId])
                ->useLegacySql(false)
                ->location('US');

            $results = $this->bigQuery->runQuery($queryJobConfig);

            foreach ($results->rows() as $row) {
                return (array) $row;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener registro', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            return null;
        }
    }

    /**
     * ✅ FINALIZAR - Actualizar datos finales y completar
     * Solo actualiza fecha_hora_fin, KPIs y Planes ya se guardaron por separado
     */
    public function finalizarFormulario($sessionId, $datosFinales)
    {
        try {
            $fechaHoraFin = $datosFinales['fecha_hora_fin'] ?? now()->toIso8601String();

            // 🆕 SOLO actualizar fecha_hora_fin (KPIs y Planes ya se guardaron)
            $mergeQuery = sprintf(
                'MERGE `%s.%s.%s` T
                USING (
                  SELECT 
                    @session_id as session_id,
                    TIMESTAMP(@fecha_hora_fin) as fecha_hora_fin
                ) S
                ON T.session_id = S.session_id
                WHEN MATCHED THEN
                  UPDATE SET 
                    fecha_hora_fin = S.fecha_hora_fin',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            Log::info('✅ Finalizando formulario', [
                'session_id' => $sessionId,
                'fecha_hora_fin' => $fechaHoraFin
            ]);

            $queryJobConfig = $this->bigQuery->query($mergeQuery)
                ->parameters([
                    'session_id' => $sessionId,
                    'fecha_hora_fin' => $fechaHoraFin
                ])
                ->useLegacySql(false)
                ->location('US');

            $queryJob = $this->bigQuery->runQuery($queryJobConfig);
            $queryJob->waitUntilComplete();

            if ($queryJob->isComplete()) {
                Log::info('✅ Formulario finalizado correctamente', [
                    'session_id' => $sessionId
                ]);

                // 🆕 GENERAR HTML DEL RESUMEN CUANDO SE FINALIZA
                $htmlResumen = $this->generarHTMLResumen($sessionId, $datosFinales);

                return [
                    'success' => true,
                    'session_id' => $sessionId,
                    'message' => 'Formulario completado exitosamente',
                    'html_generado' => !empty($htmlResumen)
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Error al finalizar formulario', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🆕 ACTUALIZAR CAMPOS PRINCIPALES desde seccion-1
     * Actualiza pais, zona, tienda en el nivel principal del registro
     */
    public function actualizarCamposPrincipales($sessionId, $mainFields)
    {
        try {
            // Validar que al menos hay algún campo
            if (empty($mainFields)) {
                Log::warning('⚠️ No hay main_fields para actualizar', ['session_id' => $sessionId]);
                return [
                    'success' => true,
                    'message' => 'Sin campos para actualizar'
                ];
            }

            // Construir campos para UPDATE
            $setClauses = [];
            
            if (!empty($mainFields['pais'])) {
                $setClauses[] = "pais = " . $this->escapeString($mainFields['pais']);
            }
            if (!empty($mainFields['zona'])) {
                $setClauses[] = "zona = " . $this->escapeString($mainFields['zona']);
            }
            if (!empty($mainFields['tienda'])) {
                $setClauses[] = "tienda = " . $this->escapeString($mainFields['tienda']);
            }

            if (empty($setClauses)) {
                Log::warning('⚠️ No hay campos válidos para actualizar', ['session_id' => $sessionId]);
                return [
                    'success' => true,
                    'message' => 'Sin campos válidos para actualizar'
                ];
            }

            // Usar MERGE para actualizar (no UPDATE por streaming buffer)
            $mergeQuery = sprintf(
                'MERGE `%s.%s.%s` T
                USING (SELECT %s as session_id) S
                ON T.session_id = S.session_id
                WHEN MATCHED THEN
                  UPDATE SET %s',
                config('services.google.project_id'),
                $this->dataset,
                $this->table,
                $this->escapeString($sessionId),
                implode(', ', $setClauses)
            );

            Log::info('🔄 Ejecutando MERGE para actualizar campos principales', [
                'session_id' => $sessionId,
                'campos_a_actualizar' => array_keys($mainFields)
            ]);

            $mergeJobConfig = $this->bigQuery->query($mergeQuery)
                ->useLegacySql(false)
                ->location('US');

            $mergeJob = $this->bigQuery->runQuery($mergeJobConfig);
            $mergeJob->waitUntilComplete();

            if ($mergeJob->isComplete()) {
                Log::info('✅ Campos principales actualizados correctamente', [
                    'session_id' => $sessionId,
                    'campos' => $mainFields
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Campos principales actualizados correctamente'
                ];
            } else {
                Log::warning('⚠️ MERGE para campos principales no se completó', [
                    'session_id' => $sessionId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'MERGE no se completó'
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar campos principales', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🆕 Actualizar KPIs en BigQuery
     */
    public function actualizarKPIs($sessionId, $kpis)
    {
        try {
            $kpisJSON = json_encode($kpis);

            // 🆕 USAR MERGE CON UNNEST PARA CONVERTIR JSON A ARRAY<STRUCT>
            $mergeQuery = sprintf(
                'MERGE `%s.%s.%s` T
                USING (
                  SELECT 
                    @session_id as session_id,
                    ARRAY(
                      SELECT AS STRUCT
                        JSON_EXTRACT_SCALAR(elem, "$.codigo_pregunta") as codigo_pregunta,
                        JSON_EXTRACT_SCALAR(elem, "$.valor") as valor,
                        JSON_EXTRACT_SCALAR(elem, "$.variacion") as variacion
                      FROM UNNEST(JSON_EXTRACT_ARRAY(@kpis_json)) as elem
                      WHERE JSON_EXTRACT_SCALAR(elem, "$.codigo_pregunta") IS NOT NULL
                        AND JSON_EXTRACT_SCALAR(elem, "$.valor") IS NOT NULL
                    ) as new_kpis
                ) S
                ON T.session_id = S.session_id
                WHEN MATCHED THEN
                  UPDATE SET kpis = S.new_kpis',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            Log::info('📊 MERGE para actualizar KPIs', [
                'session_id' => $sessionId,
                'kpis_count' => count($kpis),
                'kpis_data' => $kpis
            ]);

            $mergeJobConfig = $this->bigQuery->query($mergeQuery)
                ->parameters(['session_id' => $sessionId, 'kpis_json' => $kpisJSON])
                ->useLegacySql(false)
                ->location('US');

            $mergeJob = $this->bigQuery->runQuery($mergeJobConfig);
            $mergeJob->waitUntilComplete();

            if ($mergeJob->isComplete()) {
                Log::info('✅ KPIs actualizados correctamente', [
                    'session_id' => $sessionId,
                    'kpis_count' => count($kpis)
                ]);
                
                return [
                    'success' => true,
                    'message' => 'KPIs actualizados correctamente',
                    'session_id' => $sessionId,
                    'kpis_count' => count($kpis)
                ];
            } else {
                Log::warning('⚠️ MERGE para KPIs no se completó', [
                    'session_id' => $sessionId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'MERGE no se completó'
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Error en actualizarKPIs', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 🆕 ACTUALIZAR PLANES desde seccion-7
     * Actualiza el campo planes en BigQuery (REEMPLAZA completamente, no agrega)
     */
    public function actualizarPlanes($sessionId, $planes)
    {
        try {
            if (empty($planes)) {
                Log::warning('⚠️ No hay Planes para actualizar', ['session_id' => $sessionId]);
                return [
                    'success' => true,
                    'message' => 'Sin Planes para actualizar'
                ];
            }

            // Convertir a JSON para parámetro
            $planesJSON = json_encode($planes);

            // 🆕 USAR MERGE PARA REEMPLAZAR COMPLETAMENTE LOS PLANES (evita duplicados)
            // PARSE_DATE con formato seguro para evitar errores
            $mergeQuery = sprintf(
                'MERGE `%s.%s.%s` T
                USING (
                  SELECT 
                    @session_id as session_id,
                    ARRAY(
                      SELECT AS STRUCT
                        JSON_EXTRACT_SCALAR(elem, "$.descripcion") as descripcion,
                        SAFE.PARSE_DATE("%%Y-%%m-%%d", JSON_EXTRACT_SCALAR(elem, "$.fecha_cumplimiento")) as fecha_cumplimiento
                      FROM UNNEST(JSON_EXTRACT_ARRAY(@planes_json)) as elem
                      WHERE JSON_EXTRACT_SCALAR(elem, "$.descripcion") IS NOT NULL
                        AND JSON_EXTRACT_SCALAR(elem, "$.fecha_cumplimiento") IS NOT NULL
                    ) as new_planes
                ) S
                ON T.session_id = S.session_id
                WHEN MATCHED THEN
                  UPDATE SET planes = S.new_planes',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            Log::info('🔄 MERGE para actualizar Planes (REEMPLAZO COMPLETO)', [
                'session_id' => $sessionId,
                'planes_count' => count($planes),
                'planes_data' => $planes
            ]);

            $mergeJobConfig = $this->bigQuery->query($mergeQuery)
                ->parameters(['session_id' => $sessionId, 'planes_json' => $planesJSON])
                ->useLegacySql(false)
                ->location('US');

            $mergeJob = $this->bigQuery->runQuery($mergeJobConfig);
            $mergeJob->waitUntilComplete();

            if ($mergeJob->isComplete()) {
                Log::info('✅ Planes actualizados correctamente (REEMPLAZO)', [
                    'session_id' => $sessionId,
                    'planes_count' => count($planes)
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Planes guardados exitosamente'
                ];
            } else {
                Log::warning('⚠️ MERGE para Planes no se completó', [
                    'session_id' => $sessionId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'MERGE no se completó'
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar Planes', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 🆕 ACTUALIZAR PLANES Y FINALIZAR (SIMILAR AL MÉTODO ANTIGUO)
     * Usa MERGE para guardar planes, resumen y puntos en una sola operación
     * ✅ MERGE es mejor que UPDATE para evitar streaming buffer issues
     */
    public function actualizarPlanesYFinalizar($sessionId, $datosFinales)
    {
        try {
            $planesJSON  = json_encode($datosFinales['planes'] ?? []);
            $resumenJSON = json_encode($datosFinales['resumen_areas'] ?? []);

            $fechaHoraFin   = $datosFinales['fecha_hora_fin'] ?? now()->toIso8601String();
            $puntosTotales  = $datosFinales['puntos_totales'] ?? 0;
            $estrellas      = $datosFinales['estrellas'] ?? 0;

            Log::info('📋 Preparando MERGE para guardar Planes + Resumen (opción A)', [
                'session_id' => $sessionId,
                'planes_count' => is_array($datosFinales['planes'] ?? null) ? count($datosFinales['planes']) : 0,
                'puntos_totales' => $puntosTotales,
                'estrellas' => $estrellas,
            ]);

            $mergeQuery = sprintf(
                'MERGE `%s.%s.%s` T
                USING (
                SELECT
                    @session_id as session_id,
                    ARRAY(
                    SELECT AS STRUCT
                        JSON_EXTRACT_SCALAR(elem, "$.descripcion") as descripcion,
                        SAFE.PARSE_DATE("%%Y-%%m-%%d", JSON_EXTRACT_SCALAR(elem, "$.fecha_cumplimiento")) as fecha_cumplimiento
                    FROM UNNEST(JSON_EXTRACT_ARRAY(@planes_json)) as elem
                    WHERE JSON_EXTRACT_SCALAR(elem, "$.descripcion") IS NOT NULL
                    ) as new_planes,
                    TIMESTAMP(@fecha_hora_fin) as fecha_hora_fin,
                    PARSE_JSON(@resumen_json) as resumen_areas,
                    CAST(@puntos_totales AS FLOAT64) as puntos_totales,
                    CAST(@estrellas AS INT64) as estrellas
                ) S
                ON T.session_id = S.session_id
                WHEN MATCHED THEN
                UPDATE SET
                    planes = S.new_planes,
                    fecha_hora_fin = S.fecha_hora_fin,
                    resumen_areas = S.resumen_areas,
                    puntos_totales = S.puntos_totales,
                    estrellas = S.estrellas',
                config('services.google.project_id'),
                $this->dataset,
                $this->table
            );

            $mergeJobConfig = $this->bigQuery->query($mergeQuery)
                ->parameters([
                    'session_id' => $sessionId,
                    'planes_json' => $planesJSON,
                    'fecha_hora_fin' => $fechaHoraFin,
                    'resumen_json' => $resumenJSON,
                    'puntos_totales' => $puntosTotales,
                    'estrellas' => $estrellas,
                ])
                ->useLegacySql(false)
                ->location('US');

            $mergeJob = $this->bigQuery->runQuery($mergeJobConfig);
            $mergeJob->waitUntilComplete();

            if ($mergeJob->isComplete()) {
                Log::info('✅ MERGE completado - Planes + Resumen guardados', [
                    'session_id' => $sessionId
                ]);

                $htmlResumen = $this->generarHTMLResumen($sessionId, $datosFinales);

                return [
                    'success' => true,
                    'message' => 'Formulario finalizado exitosamente',
                    'session_id' => $sessionId,
                    'html_generado' => !empty($htmlResumen),
                    'html_ruta' => $htmlResumen
                ];
            }

            return ['success' => false, 'message' => 'MERGE no se completó'];

        } catch (\Exception $e) {
            Log::error('❌ Error en actualizarPlanesYFinalizar (opción A)', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 🆕 GENERAR HTML DEL RESUMEN USANDO PLANTILLA BLADE
     * - Lee todo desde BigQuery
     * - Usa resumen_areas/puntos_totales/estrellas guardados (opción A)
     * - Fallback: si vienen vacíos, los calcula con FormProcessingService
     */
    private function generarHTMLResumen($sessionId, $datosFinales = [])
    {
        try {
            // 1) Construir $datos desde $datosFinales (ya disponible, evita dependencia de BQ)
            $datos = [
                'tienda'           => $datosFinales['tienda'] ?? 'N/A',
                'zona'             => $datosFinales['zona'] ?? 'N/A',
                'pais'             => $datosFinales['pais'] ?? 'N/A',
                'correo_realizo'   => $datosFinales['correo_realizo'] ?? 'N/A',
                'correo_tienda'    => $datosFinales['correo_tienda'] ?? '',
                'correo_jefe_zona' => $datosFinales['correo_jefe_zona'] ?? '',
                'fecha_hora_fin'   => $datosFinales['fecha_hora_fin'] ?? now()->toIso8601String(),
                'secciones'        => $datosFinales['secciones'] ?? [],
                'kpis'             => $datosFinales['kpis'] ?? [],
                'planes'           => $datosFinales['planes'] ?? [],
                'resumen_areas'    => $datosFinales['resumen_areas'] ?? [],
                'puntos_totales'   => $datosFinales['puntos_totales'] ?? 0,
                'estrellas'        => $datosFinales['estrellas'] ?? 0,
            ];

            // 2) Intentar enriquecer con datos frescos de BigQuery (opcional — no bloquea si falla)
            try {
                $sql = sprintf(
                    'SELECT * FROM `%s.%s.%s` WHERE session_id = @session_id LIMIT 1',
                    config('services.google.project_id'),
                    $this->dataset,
                    $this->table
                );

                $queryJobConfig = $this->bigQuery->query($sql)
                    ->parameters(['session_id' => $sessionId])
                    ->useLegacySql(false)
                    ->location('US');

            $queryJob = $this->bigQuery->runQuery($queryJobConfig);
            $queryJob->waitUntilComplete();

            if (!$queryJob->isComplete()) {
                Log::warning('⚠️ Query para obtener datos no se completó', ['session_id' => $sessionId]);
                return null;
            }

                $registro = null;
                if ($queryJob->isComplete()) {
                    foreach ($queryJob->rows() as $row) {
                        $registro = $row;
                        break;
                    }
                }

                if ($registro) {
                    // Enriquecer campos base con los datos de BQ
                    $datos['tienda']         = $registro['tienda'] ?? $datos['tienda'];
                    $datos['zona']           = $registro['zona'] ?? $datos['zona'];
                    $datos['pais']           = $registro['pais'] ?? $datos['pais'];
                    $datos['correo_realizo'] = $registro['correo_realizo'] ?? $datos['correo_realizo'];
                    $datos['fecha_hora_fin'] = $registro['fecha_hora_fin'] ?? $datos['fecha_hora_fin'];

                    // Secciones desde BQ (más fiables)
                    if (!empty($registro['secciones'])) {
                        $seccionesArray = is_string($registro['secciones'])
                            ? json_decode($registro['secciones'], true)
                            : $registro['secciones'];
                        if (is_array($seccionesArray)) {
                            $datos['secciones'] = $seccionesArray;
                        }
                    }

                    // KPIs desde BQ
                    if (!empty($registro['kpis'])) {
                        $kpisArray = is_string($registro['kpis'])
                            ? json_decode($registro['kpis'], true)
                            : $registro['kpis'];
                        if (is_array($kpisArray)) {
                            $datos['kpis'] = $kpisArray;
                        }
                    }

                    // Planes desde BQ (normalizando objetos)
                    if (!empty($registro['planes'])) {
                        $planesRaw = is_string($registro['planes'])
                            ? json_decode($registro['planes'], true)
                            : $registro['planes'];

                        if (is_array($planesRaw)) {
                            $planesNormalizados = [];
                            foreach ($planesRaw as $plan) {
                                if (is_object($plan)) {
                                    $planesNormalizados[] = [
                                        'descripcion'       => $plan->descripcion ?? '',
                                        'fecha_cumplimiento' => $plan->fecha_cumplimiento ?? ''
                                    ];
                                } elseif (is_array($plan)) {
                                    $planesNormalizados[] = [
                                        'descripcion'       => $plan['descripcion'] ?? '',
                                        'fecha_cumplimiento' => $plan['fecha_cumplimiento'] ?? ''
                                    ];
                                }
                            }
                            $datos['planes'] = $planesNormalizados;
                        }
                    }

                    // Resumen desde BQ
                    if (!empty($registro['resumen_areas'])) {
                        if (is_string($registro['resumen_areas'])) {
                            $decoded = json_decode($registro['resumen_areas'], true);
                            if (is_array($decoded)) {
                                $datos['resumen_areas'] = $decoded;
                            }
                        } elseif (is_array($registro['resumen_areas'])) {
                            $datos['resumen_areas'] = $registro['resumen_areas'];
                        } elseif (is_object($registro['resumen_areas'])) {
                            $datos['resumen_areas'] = json_decode(json_encode($registro['resumen_areas']), true) ?? [];
                        }
                    }

                    if (isset($registro['puntos_totales']) && $registro['puntos_totales'] !== null) {
                        $datos['puntos_totales'] = (float) $registro['puntos_totales'];
                    }
                    if (isset($registro['estrellas']) && $registro['estrellas'] !== null) {
                        $datos['estrellas'] = (int) $registro['estrellas'];
                    }
                } else {
                    Log::warning('⚠️ BQ no devolvió registro para HTML — usando datosFinales', ['session_id' => $sessionId]);
                }
            } catch (\Exception $eBq) {
                Log::warning('⚠️ No se pudo consultar BQ para enriquecer HTML — usando datosFinales', [
                    'session_id' => $sessionId,
                    'error' => $eBq->getMessage()
                ]);
            }

            // 3) Fallback de resumen si aún está vacío
            if (empty($datos['resumen_areas']) || $datos['puntos_totales'] == 0) {
                try {
                    $resumenCalc = $this->formProcessing->calcularResumen($datos['secciones'], $datos['kpis']);

                    $datos['resumen_areas'] = $resumenCalc['resumen_areas'] ?? [];
                    $datos['puntos_totales'] = $resumenCalc['puntos_totales'] ?? 0;
                    $datos['estrellas'] = $resumenCalc['estrellas'] ?? 0;

                    Log::info('🧠 Fallback: resumen calculado al vuelo para HTML', [
                        'session_id' => $sessionId,
                        'puntos' => $datos['puntos_totales'],
                        'estrellas' => $datos['estrellas'],
                        'resumen_count' => is_array($datos['resumen_areas']) ? count($datos['resumen_areas']) : 0
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('⚠️ No se pudo calcular resumen fallback para HTML', [
                        'session_id' => $sessionId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('📊 Datos listos para HTML', [
                'session_id' => $sessionId,
                'secciones_count' => is_array($datos['secciones']) ? count($datos['secciones']) : 0,
                'kpis_count' => is_array($datos['kpis']) ? count($datos['kpis']) : 0,
                'planes_count' => is_array($datos['planes']) ? count($datos['planes']) : 0,
                'puntos_totales' => $datos['puntos_totales'],
                'estrellas' => $datos['estrellas'],
                'resumen_count' => is_array($datos['resumen_areas']) ? count($datos['resumen_areas']) : 0,
            ]);

            // 8) Render blade
            $html = View::make('emails.visita_confirmacion', ['datos' => $datos])->render();

            // 9) Guardar en public/correos
            $rutaCorreos = env('CORREOS_PUBLIC_PATH', public_path('correos'));
            if (!is_dir($rutaCorreos)) {
                mkdir($rutaCorreos, 0755, true);
            }

            $nombreArchivo = $sessionId . '-resumen.html';
            $rutaCompleta = $rutaCorreos . DIRECTORY_SEPARATOR . $nombreArchivo;

            file_put_contents($rutaCompleta, $html);

            Log::info('✅ HTML de resumen generado y guardado', [
                'session_id' => $sessionId,
                'archivo' => $nombreArchivo,
                'ruta' => $rutaCompleta
            ]);

            return $rutaCompleta;

        } catch (\Exception $e) {
            Log::error('❌ Error generando HTML de resumen', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * 🆕 Obtener tabla para insertRows directo
     * Retorna la tabla de BigQuery para insertar registros directos
     */
    public function obtenerTabla()
    {
        return $this->bigQuery
            ->dataset($this->dataset)
            ->table($this->table);
    }
}
