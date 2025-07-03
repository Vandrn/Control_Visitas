<?php

namespace App\Models;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Hash;

class Usuario
{
    protected $bigQuery;
    protected $table = 'usuarios';
    protected $dataset = 'OPB';
    protected $projectId = 'adoc-bi-dev';
    // Tabla con campos anidados para nuevas consultas
    protected $nestedTable = 'GR_nested';

    public function __construct()
    {
        $this->bigQuery = new BigQueryClient([
            'projectId' => config('admin.bigquery.project_id'),
            'keyFilePath' => storage_path('app' . config('admin.bigquery.key_file')),
        ]);
        $this->nestedTable = config('admin.bigquery.visitas_nested_table', $this->nestedTable);
    }

    /**
     * Buscar usuario por email
     */
    public function findByEmail($email)
    {
        $query = sprintf(
            'SELECT id, nombre, email, password_hash, rol, activo, pais_acceso, created_at, updated_at FROM `%s.%s.%s` WHERE email = @email LIMIT 1',
            $this->projectId,
            $this->dataset,
            $this->table
        );

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters(['email' => $email]);

        $results = $this->bigQuery->runQuery($queryJobConfig);

        foreach ($results->rows() as $row) {
            return (array) $row;
        }

        return null;
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword($password, $hash)
    {
        return Hash::check($password, $hash);
    }

    /**
     * Crear hash de contraseña
     */
    public function hashPassword($password)
    {
        return Hash::make($password);
    }

    /**
     * Buscar usuario por ID
     */
    public function findById($id)
    {
        $query = sprintf(
            'SELECT * FROM `%s.%s.%s` WHERE id = @id LIMIT 1',
            $this->projectId,
            $this->dataset,
            $this->table
        );

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters(['id' => $id]);

        $results = $this->bigQuery->runQuery($queryJobConfig);

        foreach ($results->rows() as $row) {
            return (array) $row;
        }

        return null;
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAll($limit = 50)
    {
        $query = sprintf(
            'SELECT * FROM `%s.%s.%s` ORDER BY created_at DESC LIMIT %d',
            $this->projectId,
            $this->dataset,
            $this->table,
            $limit
        );

        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $usuarios = [];
        foreach ($results->rows() as $row) {
            $usuarios[] = (array) $row;
        }

        return $usuarios;
    }

    /**
     * Crear nuevo usuario
     */
    public function create($data)
    {
        $table = $this->bigQuery->dataset($this->dataset)->table($this->table);
        
        $row = [
            'data' => [
                'id' => uniqid('user_', true),
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'password_hash' => $this->hashPassword($data['password']),
                'rol' => $data['rol'] ?? 'evaluador',
                'activo' => $data['activo'] ?? true,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]
        ];

        $insertResponse = $table->insertRows([$row]);
        
        return $insertResponse->isSuccessful();
    }
/**
     * Obtener estadísticas generales de visitas
     */
    public function getEstadisticasVisitas($filtros = [], $userData = null)
{
    // Si no se pasa userData, obtenerlo de la sesión
    if (!$userData) {
        $userData = session('admin_user');
    }
    
    $whereClause = $this->buildWhereClause($filtros, $userData);
    
    $query = sprintf(
        'SELECT 
            COUNT(*) as total_visitas,
            COUNT(DISTINCT PAIS) as total_paises,
            COUNT(DISTINCT ZONA) as total_zonas,
            COUNT(DISTINCT TIENDA) as total_tiendas,
            COUNT(DISTINCT CORREO_REALIZO) as total_evaluadores,
            DATE(MIN(FECHA_HORA_INICIO)) as fecha_primera_visita,
            DATE(MAX(FECHA_HORA_INICIO)) as fecha_ultima_visita
        FROM `%s.%s.GR_nuevo` %s',
        $this->projectId,
        $this->dataset,
        $whereClause
    );
    
    $queryJobConfig = $this->bigQuery->query($query);
    $results = $this->bigQuery->runQuery($queryJobConfig);
    
    foreach ($results->rows() as $row) {
        return (array) $row;
    }
    
        return [
            'total_visitas' => 0,
        'total_paises' => 0,
        'total_zonas' => 0,
        'total_tiendas' => 0,
        'total_evaluadores' => 0,
        'fecha_primera_visita' => null,
        'fecha_ultima_visita' => null
    ];
}

    /**
     * Estadísticas usando la tabla anidada
     */
    public function getEstadisticasVisitasNested($filtros = [], $userData = null)
    {
        if (!$userData) {
            $userData = session('admin_user');
        }

        $whereClause = $this->buildWhereClause($filtros, $userData);

        $query = sprintf(
            'SELECT
                COUNT(*) as total_visitas,
                COUNT(DISTINCT PAIS) as total_paises,
                COUNT(DISTINCT ZONA) as total_zonas,
                COUNT(DISTINCT TIENDA) as total_tiendas,
                COUNT(DISTINCT CORREO_REALIZO) as total_evaluadores,
                DATE(MIN(FECHA_HORA_INICIO)) as fecha_primera_visita,
                DATE(MAX(FECHA_HORA_INICIO)) as fecha_ultima_visita
            FROM `%s.%s.%s` %s',
            $this->projectId,
            $this->dataset,
            $this->nestedTable,
            $whereClause
        );

        $results = $this->bigQuery->runQuery($this->bigQuery->query($query));

        foreach ($results->rows() as $row) {
            return (array) $row;
        }

        return [
            'total_visitas' => 0,
            'total_paises' => 0,
            'total_zonas' => 0,
            'total_tiendas' => 0,
            'total_evaluadores' => 0,
            'fecha_primera_visita' => null,
            'fecha_ultima_visita' => null
        ];
    }

    /**
     * Obtener visitas con paginación y filtros
     */
    public function getVisitasPaginadas($filtros = [], $page = 1, $perPage = 20, $userData = null)
    {
        $whereClause = $this->buildWhereClause($filtros, $userData); // ✅ Pasar userData
        $offset = ($page - 1) * $perPage;
        
        $query = sprintf(
            'SELECT 
                id,
                FECHA_HORA_INICIO,
                CORREO_REALIZO,
                LIDER_ZONA,
                PAIS,
                ZONA,
                TIENDA,
                UBICACION,
                -- Calcular puntuación promedio de operaciones
                (CAST(PREG_01_01 AS FLOAT64) + CAST(PREG_01_02 AS FLOAT64) + CAST(PREG_01_03 AS FLOAT64) + 
                 CAST(PREG_01_04 AS FLOAT64) + CAST(PREG_01_05 AS FLOAT64)) / 5 as puntuacion_operaciones,
                -- Calcular puntuación promedio general (ejemplo con primeras 10 preguntas)
                (CAST(PREG_01_01 AS FLOAT64) + CAST(PREG_01_02 AS FLOAT64) + CAST(PREG_01_03 AS FLOAT64) + 
                 CAST(PREG_01_04 AS FLOAT64) + CAST(PREG_01_05 AS FLOAT64) + CAST(PREG_01_06 AS FLOAT64) + 
                 CAST(PREG_01_07 AS FLOAT64) + CAST(PREG_01_08 AS FLOAT64) + CAST(PREG_01_09 AS FLOAT64) + 
                 CAST(PREG_01_10 AS FLOAT64)) / 10 as puntuacion_general
            FROM `%s.%s.GR_nuevo` 
            %s
            ORDER BY FECHA_HORA_INICIO DESC
            LIMIT %d OFFSET %d',
            $this->projectId,
            $this->dataset,
            $whereClause,
            $perPage,
            $offset
        );

        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $visitas = [];
        foreach ($results->rows() as $row) {
            $visitas[] = (array) $row;
        }

        return $visitas;
    }

    /**
     * Versión para esquema anidado usando UNNEST
     */
    public function getVisitasPaginadasNested($filtros = [], $page = 1, $perPage = 20, $userData = null)
    {
        $whereClause = $this->buildWhereClause($filtros, $userData);
        $offset = ($page - 1) * $perPage;

        $query = sprintf(
            'SELECT
                v.id,
                v.FECHA_HORA_INICIO,
                v.CORREO_REALIZO,
                v.LIDER_ZONA,
                v.PAIS,
                v.ZONA,
                v.TIENDA,
                v.UBICACION,
                (SELECT AVG(CAST(p.valor AS FLOAT64))
                    FROM UNNEST(v.secciones) s
                    CROSS JOIN UNNEST(s.preguntas) p) AS puntuacion_general,
                (SELECT AVG(CAST(p.valor AS FLOAT64))
                    FROM UNNEST(v.secciones) s
                    CROSS JOIN UNNEST(s.preguntas) p
                    WHERE LOWER(s.id) = "operaciones") AS puntuacion_operaciones
            FROM `%s.%s.%s` v
            %s
            ORDER BY FECHA_HORA_INICIO DESC
            LIMIT %d OFFSET %d',
            $this->projectId,
            $this->dataset,
            $this->nestedTable,
            $whereClause,
            $perPage,
            $offset
        );

        $results = $this->bigQuery->runQuery($this->bigQuery->query($query));

        $visitas = [];
        foreach ($results->rows() as $row) {
            $visitas[] = (array) $row;
        }

        return $visitas;
    }

    /**
     * Obtener países disponibles según permisos del usuario
     */
    public function getPaisesDisponibles($userData = null)
    {
        $query = sprintf(
            'SELECT DISTINCT PAIS FROM `%s.%s.GR_nuevo` WHERE PAIS IS NOT NULL',
            $this->projectId,
            $this->dataset
        );
        
        // Si es evaluador_pais, filtrar solo su país
        if ($userData && $userData['rol'] === 'evaluador_pais' && isset($userData['pais_acceso']) && $userData['pais_acceso'] !== 'ALL') {
            $query .= " AND PAIS = '" . $userData['pais_acceso'] . "'";
        }
        
        $query .= ' ORDER BY PAIS';
    
        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);
    
        $paises = [];
        foreach ($results->rows() as $row) {
            $paises[] = $row['PAIS'];
        }
    
        return $paises;
    }

    /**
     * Obtener evaluadores disponibles
     */
    public function getEvaluadoresDisponibles()
    {
        $query = sprintf(
            'SELECT DISTINCT CORREO_REALIZO, LIDER_ZONA 
            FROM `%s.%s.GR_nuevo` 
            WHERE CORREO_REALIZO IS NOT NULL 
            ORDER BY CORREO_REALIZO',
            $this->projectId,
            $this->dataset
        );

        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $evaluadores = [];
        foreach ($results->rows() as $row) {
            $evaluadores[] = [
                'email' => $row['CORREO_REALIZO'],
                'nombre' => $row['LIDER_ZONA'] ?? $row['CORREO_REALIZO']
            ];
        }

        return $evaluadores;
    }

    /**
     * Construir cláusula WHERE basada en filtros
     */
    private function buildWhereClause($filtros = [], $userData = null)
{
    $conditions = [];

    // Filtro por rol de usuario (CÓDIGO EXISTENTE QUE FUNCIONA)
    if (!$userData) {
        $userData = session('admin_user');
    }
    
    if ($userData && $userData['rol'] === 'evaluador') {
        // Los evaluadores solo ven sus propias visitas
        $conditions[] = "CORREO_REALIZO = '" . $userData['email'] . "'";
    }

    // AGREGAR SOLO ESTO - SIMPLE Y DIRECTO:
    // Filtro automático por país para evaluador_pais
    if ($userData && $userData['rol'] === 'evaluador_pais' && isset($userData['pais_acceso']) && $userData['pais_acceso'] !== 'ALL') {
        $conditions[] = "PAIS = '" . $userData['pais_acceso'] . "'";
    }

    // Filtros manuales (CÓDIGO EXISTENTE)
    if (!empty($filtros['fecha_inicio'])) {
        $conditions[] = "DATE(FECHA_HORA_INICIO) >= '" . $filtros['fecha_inicio'] . "'";
    }
    if (!empty($filtros['fecha_fin'])) {
        $conditions[] = "DATE(FECHA_HORA_INICIO) <= '" . $filtros['fecha_fin'] . "'";
    }
    if (!empty($filtros['pais'])) {
        $conditions[] = "PAIS = '" . $filtros['pais'] . "'";
    }
    if (!empty($filtros['tienda'])) {
        $conditions[] = "TIENDA LIKE '%" . $filtros['tienda'] . "%'";
    }
    if (!empty($filtros['evaluador'])) {
        $conditions[] = "CORREO_REALIZO = '" . $filtros['evaluador'] . "'";
    }

    return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
}

    /**
     * Contar total de visitas con filtros
     */
public function contarVisitas($filtros = [], $userData = null)
{
    $whereClause = $this->buildWhereClause($filtros, $userData); // ✅ Pasar userData
        
        $query = sprintf(
            'SELECT COUNT(*) as total FROM `%s.%s.GR_nuevo` %s',
            $this->projectId,
            $this->dataset,
            $whereClause
        );

        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        foreach ($results->rows() as $row) {
            return (int) $row['total'];
        }

        return 0;
    }

    /**
     * Contar visitas en la tabla anidada
     */
    public function contarVisitasNested($filtros = [], $userData = null)
    {
        $whereClause = $this->buildWhereClause($filtros, $userData);

        $query = sprintf(
            'SELECT COUNT(*) as total FROM `%s.%s.%s` %s',
            $this->projectId,
            $this->dataset,
            $this->nestedTable,
            $whereClause
        );

        $results = $this->bigQuery->runQuery($this->bigQuery->query($query));

        foreach ($results->rows() as $row) {
            return (int) $row['total'];
        }

        return 0;
    }
/**
     * Obtener detalle completo de una visita por ID
     */
    public function getVisitaCompleta($id, $userRole = null, $userEmail = null)
    {
        // Construir query base
$query = sprintf(
    'SELECT concat(gr.PAIS,left(gr.TIENDA,3)) BV_PAIS_TIENDA, a.GEO as TIENDA_COORDENADAS, gr.*
    FROM `%s.%s.GR_nuevo` gr
    LEFT JOIN (
        SELECT concat(dsm.LATITUD,",",replace(dsm.LONGITUD,"\'","")) GEO, dsm.PAIS_TIENDA
        FROM `%s.bi_lab.dim_store_master` dsm
        WHERE dsm.LATITUD NOT IN ("nan")
    ) a ON concat(gr.PAIS,left(gr.TIENDA,3)) = a.PAIS_TIENDA
    WHERE gr.id = @id',
    $this->projectId,
    $this->dataset,
    $this->projectId
);
        // Si es evaluador, verificar que solo vea sus visitas
        if ($userRole === 'evaluador' && $userEmail) {
            $query .= ' AND CORREO_REALIZO = @user_email';
        }

        $query .= ' LIMIT 1';

        $parameters = ['id' => $id];
        if ($userRole === 'evaluador' && $userEmail) {
            $parameters['user_email'] = $userEmail;
        }

        $queryJobConfig = $this->bigQuery->query($query)->parameters($parameters);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        foreach ($results->rows() as $row) {
            return (array) $row;
        }

        return null;
    }

    /**
     * Obtener visita utilizando campos anidados
     */
    public function getVisitaNested($id, $userRole = null, $userEmail = null)
    {
        $query = sprintf(
            'SELECT * FROM `%s.%s.%s` WHERE id = @id',
            $this->projectId,
            $this->dataset,
            $this->nestedTable
        );

        if ($userRole === 'evaluador' && $userEmail) {
            $query .= ' AND CORREO_REALIZO = @user_email';
        }

        $query .= ' LIMIT 1';

        $params = ['id' => $id];
        if ($userRole === 'evaluador' && $userEmail) {
            $params['user_email'] = $userEmail;
        }

        $job = $this->bigQuery->query($query)->parameters($params);
        $results = $this->bigQuery->runQuery($job);

        foreach ($results->rows() as $row) {
            $data = (array) $row;
            // Convertir campos anidados a arreglos manejables
            $data['secciones'] = array_map(function ($s) {
                return (array) $s;
            }, $data['secciones'] ?? []);
            $data['planes'] = array_map(function ($p) {
                return (array) $p;
            }, $data['planes'] ?? []);
            $data['kpis'] = array_map(function ($k) {
                return (array) $k;
            }, $data['kpis'] ?? []);
            return $data;
        }

        return null;
    }

    /**
     * Dar forma a la visita anidada para las vistas existentes
     */
    public function formatearVisitaNested($data)
    {
        if (!$data) {
            return null;
        }

        $visita = [
            'id' => $data['id'],
            'fecha_hora_inicio' => $data['FECHA_HORA_INICIO'] ?? null,
            'fecha_hora_fin' => $data['FECHA_HORA_FIN'] ?? null,
            'correo_realizo' => $data['CORREO_REALIZO'] ?? null,
            'lider_zona' => $data['LIDER_ZONA'] ?? null,
            'pais' => $data['PAIS'] ?? null,
            'zona' => $data['ZONA'] ?? null,
            'tienda' => $data['TIENDA'] ?? null,
            'ubicacion' => $data['UBICACION'] ?? null,
        ];

        // Procesar secciones
        foreach ($data['secciones'] ?? [] as $sec) {
            $idSeccion = strtolower($sec['id'] ?? ($sec['nombre'] ?? ''));
            $pregs = [];
            foreach ($sec['preguntas'] ?? [] as $p) {
                $pregs[$p['id']] = $p['valor'];
            }
            $visita[$idSeccion] = [
                'preguntas' => $pregs,
                'observaciones' => $sec['observaciones'] ?? null,
                'imagen_url' => $sec['imagen_url'] ?? null,
            ];
        }

        // Planes de acción
        $planes = [];
        foreach ($data['planes'] ?? [] as $plan) {
            $planes[] = [
                'descripcion' => $plan['descripcion'] ?? null,
                'fecha' => $plan['fecha'] ?? null,
            ];
        }
        if ($planes) {
            $visita['planes'] = $planes;
        }

        // KPIs independientes
        if (!empty($data['kpis'])) {
            $visita['kpis'] = $data['kpis'];
        }

        return $visita;
    }

    /**
     * Obtener URLs de imágenes de una visita
     */
    public function getImagenesVisita($id, $userRole = null, $userEmail = null)
    {
        $visita = $this->getVisitaCompleta($id, $userRole, $userEmail);
        
        if (!$visita) {
            return [];
        }

        $imagenes = [];
        
        // Mapear imágenes por área
        $areasImagenes = [
            'operaciones' => [
                'titulo' => 'Operaciones',
                'url' => $visita['IMG_OBS_OPE_URL'] ?? null,
                'observaciones' => $visita['OBS_01_01'] ?? null
            ],
            'administracion' => [
                'titulo' => 'Administración',
                'url' => $visita['IMG_OBS_ADM_URL'] ?? null,
                'observaciones' => $visita['OBS_02_01'] ?? null
            ],
            'producto' => [
                'titulo' => 'Producto',
                'url' => $visita['IMG_OBS_PRO_URL'] ?? null,
                'observaciones' => $visita['OBS_03_01'] ?? null
            ],
            'personal' => [
                'titulo' => 'Personal',
                'url' => $visita['IMG_OBS_PER_URL'] ?? null,
                'observaciones' => $visita['OBS_04_01'] ?? null
            ],
            'kpis' => [
                'titulo' => 'KPIs',
                'url' => $visita['IMG_OBS_KPI_URL'] ?? null,
                'observaciones' => $visita['OBS_05_01'] ?? null
            ]
        ];

        // Filtrar solo las imágenes que existen
        foreach ($areasImagenes as $area => $data) {
            if (!empty($data['url'])) {
                $imagenes[$area] = $data;
            }
        }

        return $imagenes;
    }

    /**
     * Procesar y estructurar datos de la visita para display
     */
    public function procesarDatosVisita($visita)
    {
        if (!$visita) {
            return null;
        }

        return [
            // Información general
            'id' => $visita['id'],
            'fecha_hora_inicio' => $visita['FECHA_HORA_INICIO'],
            'fecha_hora_fin' => $visita['FECHA_HORA_FIN'] ?? null,
            'correo_realizo' => $visita['CORREO_REALIZO'],
            'lider_zona' => $visita['LIDER_ZONA'],
            'pais' => $visita['PAIS'],
            'zona' => $visita['ZONA'],
            'tienda' => $visita['TIENDA'],
            'ubicacion' => $visita['UBICACION'],

            // Sección 1: Operaciones (14 preguntas)
            'operaciones' => [
                'preguntas' => [
                    'PREG_01_01' => $visita['PREG_01_01'] ?? null,
                    'PREG_01_02' => $visita['PREG_01_02'] ?? null,
                    'PREG_01_03' => $visita['PREG_01_03'] ?? null,
                    'PREG_01_04' => $visita['PREG_01_04'] ?? null,
                    'PREG_01_05' => $visita['PREG_01_05'] ?? null,
                    'PREG_01_06' => $visita['PREG_01_06'] ?? null,
                    'PREG_01_07' => $visita['PREG_01_07'] ?? null,
                    'PREG_01_08' => $visita['PREG_01_08'] ?? null,
                    'PREG_01_09' => $visita['PREG_01_09'] ?? null,
                    'PREG_01_10' => $visita['PREG_01_10'] ?? null,
                    'PREG_01_11' => $visita['PREG_01_11'] ?? null,
                    'PREG_01_12' => $visita['PREG_01_12'] ?? null,
                    'PREG_01_13' => $visita['PREG_01_13'] ?? null,
                    'PREG_01_14' => $visita['PREG_01_14'] ?? null,
                ],
                'observaciones' => $visita['OBS_01_01'] ?? null,
                'imagen_url' => $visita['IMG_OBS_OPE_URL'] ?? null,
            ],

            // Sección 2: Administración (8 preguntas)
            'administracion' => [
                'preguntas' => [
                    'PREG_02_01' => $visita['PREG_02_01'] ?? null,
                    'PREG_02_02' => $visita['PREG_02_02'] ?? null,
                    'PREG_02_03' => $visita['PREG_02_03'] ?? null,
                    'PREG_02_04' => $visita['PREG_02_04'] ?? null,
                    'PREG_02_05' => $visita['PREG_02_05'] ?? null,
                    'PREG_02_06' => $visita['PREG_02_06'] ?? null,
                    'PREG_02_07' => $visita['PREG_02_07'] ?? null,
                    'PREG_02_08' => $visita['PREG_02_08'] ?? null,
                ],
                'observaciones' => $visita['OBS_02_01'] ?? null,
                'imagen_url' => $visita['IMG_OBS_ADM_URL'] ?? null,
            ],

            // Sección 3: Producto (8 preguntas)
            'producto' => [
                'preguntas' => [
                    'PREG_03_01' => $visita['PREG_03_01'] ?? null,
                    'PREG_03_02' => $visita['PREG_03_02'] ?? null,
                    'PREG_03_03' => $visita['PREG_03_03'] ?? null,
                    'PREG_03_04' => $visita['PREG_03_04'] ?? null,
                    'PREG_03_05' => $visita['PREG_03_05'] ?? null,
                    'PREG_03_06' => $visita['PREG_03_06'] ?? null,
                    'PREG_03_07' => $visita['PREG_03_07'] ?? null,
                    'PREG_03_08' => $visita['PREG_03_08'] ?? null,
                ],
                'observaciones' => $visita['OBS_03_01'] ?? null,
                'imagen_url' => $visita['IMG_OBS_PRO_URL'] ?? null,
            ],

            // Sección 4: Personal (15 preguntas)
            'personal' => [
                'preguntas' => [
                    'PREG_04_01' => $visita['PREG_04_01'] ?? null,
                    'PREG_04_02' => $visita['PREG_04_02'] ?? null,
                    'PREG_04_03' => $visita['PREG_04_03'] ?? null,
                    'PREG_04_04' => $visita['PREG_04_04'] ?? null,
                    'PREG_04_05' => $visita['PREG_04_05'] ?? null,
                    'PREG_04_06' => $visita['PREG_04_06'] ?? null,
                    'PREG_04_07' => $visita['PREG_04_07'] ?? null,
                    'PREG_04_08' => $visita['PREG_04_08'] ?? null,
                    'PREG_04_09' => $visita['PREG_04_09'] ?? null,
                    'PREG_04_10' => $visita['PREG_04_10'] ?? null,
                    'PREG_04_11' => $visita['PREG_04_11'] ?? null,
                    'PREG_04_12' => $visita['PREG_04_12'] ?? null,
                    'PREG_04_13' => $visita['PREG_04_13'] ?? null,
                    'PREG_04_14' => $visita['PREG_04_14'] ?? null,
                    'PREG_04_15' => $visita['PREG_04_15'] ?? null,
                ],
                'observaciones' => $visita['OBS_04_01'] ?? null,
                'imagen_url' => $visita['IMG_OBS_PER_URL'] ?? null,
            ],

            // Sección 5: KPIs (6 preguntas)
            'kpis' => [
                'preguntas' => [
                    'PREG_05_01' => $visita['PREG_05_01'] ?? null,
                    'PREG_05_02' => $visita['PREG_05_02'] ?? null,
                    'PREG_05_03' => $visita['PREG_05_03'] ?? null,
                    'PREG_05_04' => $visita['PREG_05_04'] ?? null,
                    'PREG_05_05' => $visita['PREG_05_05'] ?? null,
                    'PREG_05_06' => $visita['PREG_05_06'] ?? null,
                ],
                'observaciones' => $visita['OBS_05_01'] ?? null,
                'imagen_url' => $visita['IMG_OBS_KPI_URL'] ?? null,
            ],

            // Planes de acción
            'planes_accion' => [
                'PLAN_01' => [
                    'descripcion' => $visita['PLAN_01'] ?? null,
                    'fecha' => $visita['FECHA_PLAN_01'] ?? null,
                ],
                'PLAN_02' => [
                    'descripcion' => $visita['PLAN_02'] ?? null,
                    'fecha' => $visita['FECHA_PLAN_02'] ?? null,
                ],
                'PLAN_03' => [
                    'descripcion' => $visita['PLAN_03'] ?? null,
                    'fecha' => $visita['FECHA_PLAN_03'] ?? null,
                ],
                'PLAN_04' => [
                    'descripcion' => $visita['PLAN_04'] ?? null,
                    'fecha' => $visita['FECHA_PLAN_04'] ?? null,
                ],
                'PLAN_05' => [
                    'descripcion' => $visita['PLAN_05'] ?? null,
                    'fecha' => $visita['FECHA_PLAN_05'] ?? null,
                ],
                'PLAN_ADIC' => [
                    'descripcion' => $visita['PLAN_ADIC'] ?? null,
                    'fecha' => $visita['FECHA_PLAN_ADIC'] ?? null,
                ],
            ],
        ];
    }

    /**
     * Calcular puntuaciones por sección
     */
    public function calcularPuntuaciones($datosVisita)
    {
        $puntuaciones = [];

        $secciones = ['operaciones', 'administracion', 'producto', 'personal', 'kpis'];

        foreach ($secciones as $seccion) {
            if (isset($datosVisita[$seccion]['preguntas'])) {
                $preguntas = $datosVisita[$seccion]['preguntas'];
                $total = 0;
                $contador = 0;

                foreach ($preguntas as $pregunta => $valor) {
                    if ($valor !== null && is_numeric($valor)) {
                        $total += floatval($valor);
                        $contador++;
                    }
                }

                if ($contador > 0) {
                    $promedio = $total / $contador;
                    $puntuaciones[$seccion] = [
                        'promedio' => $promedio,
                        'estrellas' => round($promedio * 5, 1), // Convertir 0-1 a 0-5
                        'porcentaje' => round($promedio * 100, 1),
                        'total_preguntas' => $contador
                    ];
                } else {
                    $puntuaciones[$seccion] = [
                        'promedio' => 0,
                        'estrellas' => 0,
                        'porcentaje' => 0,
                        'total_preguntas' => 0
                    ];
                }
            }
        }

        // Calcular puntuación general
        $totalSecciones = count($puntuaciones);
        $sumaPromedios = array_sum(array_column($puntuaciones, 'promedio'));
        
        $puntuaciones['general'] = [
            'promedio' => $totalSecciones > 0 ? $sumaPromedios / $totalSecciones : 0,
            'estrellas' => $totalSecciones > 0 ? round(($sumaPromedios / $totalSecciones) * 5, 1) : 0,
            'porcentaje' => $totalSecciones > 0 ? round(($sumaPromedios / $totalSecciones) * 100, 1) : 0,
        ];

        return $puntuaciones;
    }
    
    /**
     * 📍 Calcular distancia entre dos coordenadas usando fórmula Haversine
     */
    public function calcularDistancia($lat1, $lng1, $lat2, $lng2)
    {
        $radioTierra = 6371000; // Radio de la Tierra en metros
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $radioTierra * $c; // Distancia en metros
    }

    /**
     * 📍 Validar distancia de una visita
     */
    public function validarDistanciaVisita($visitaData)
    {
        // Extraer ubicación del usuario desde la visita
        $ubicacionUsuario = $visitaData['UBICACION'] ?? null;
        $coordenadasTienda = $visitaData['TIENDA_COORDENADAS'] ?? null;
        
        if (!$ubicacionUsuario || !$coordenadasTienda) {
            return [
                'valida' => false,
                'distancia' => null,
                'mensaje' => '⚠️ No se encontraron coordenadas para validar',
                'estado' => 'sin_datos'
            ];
        }
        
        // Parsear coordenadas del usuario (formato: "lat,lng")
        $coordsUsuario = explode(',', $ubicacionUsuario);
        if (count($coordsUsuario) !== 2) {
            return [
                'valida' => false,
                'distancia' => null,
                'mensaje' => '⚠️ Formato de ubicación de usuario inválido',
                'estado' => 'error'
            ];
        }
        
        // Parsear coordenadas de la tienda (formato: "lat,lng")
        $coordsTienda = explode(',', $coordenadasTienda);
        if (count($coordsTienda) !== 2) {
            return [
                'valida' => false,
                'distancia' => null,
                'mensaje' => '⚠️ Formato de coordenadas de tienda inválido',
                'estado' => 'error'
            ];
        }
        
        $latUsuario = floatval(trim($coordsUsuario[0]));
        $lngUsuario = floatval(trim($coordsUsuario[1]));
        $latTienda = floatval(trim($coordsTienda[0]));
        $lngTienda = floatval(trim($coordsTienda[1]));
        
        // Calcular distancia
        $distancia = $this->calcularDistancia($latUsuario, $lngUsuario, $latTienda, $lngTienda);
        $distanciaRedondeada = round($distancia);
        
        // Determinar validez (≤ 50 metros = válida)
        $esValida = $distanciaRedondeada <= 50;
        
        return [
            'valida' => $esValida,
            'distancia' => $distanciaRedondeada,
            'mensaje' => $esValida 
                ? "✅ Visita válida: {$distanciaRedondeada} metros de la tienda"
                : "❌ Visita sospechosa: {$distanciaRedondeada} metros de la tienda (muy lejos)",
            'estado' => $esValida ? 'valida' : 'invalida',
            'coords_usuario' => ['lat' => $latUsuario, 'lng' => $lngUsuario],
            'coords_tienda' => ['lat' => $latTienda, 'lng' => $lngTienda]
        ];
    }

    /**
     * 📍 Obtener validación de distancia para una visita específica
     */
    public function getValidacionDistancia($id, $userRole = null, $userEmail = null)
    {
        $visitaData = $this->getVisitaCompleta($id, $userRole, $userEmail);
        
        if (!$visitaData) {
            return [
                'valida' => false,
                'distancia' => null,
                'mensaje' => '❌ Visita no encontrada',
                'estado' => 'error'
            ];
        }
        
        return $this->validarDistanciaVisita($visitaData);
    }
    
    /**
 * Verificar si el usuario tiene acceso a un país específico
 */
public function tieneAccesoPais($pais, $userData)
{
    // Admin siempre tiene acceso completo
    if ($userData['rol'] === 'admin') {
        return true;
    }
    
    // Evaluador normal tiene acceso completo
    if ($userData['rol'] === 'evaluador') {
        return true;
    }
    
    // Evaluador por país solo accede a su país asignado
    if ($userData['rol'] === 'evaluador_pais') {
        $paisAcceso = $userData['pais_acceso'] ?? null;
        return $paisAcceso === 'ALL' || $paisAcceso === $pais;
    }
    
    return false;
}

/**
 * Obtener tiendas disponibles según permisos del usuario
 */
public function getTiendasDisponibles($userData = null)
{
    $query = sprintf(
        'SELECT DISTINCT a.TIENDA FROM `%s.%s.GR_nuevo` a WHERE a.TIENDA IS NOT NULL',
        $this->projectId,
        $this->dataset
    );
    
    // Si es evaluador_pais, filtrar solo su país
    if ($userData && $userData['rol'] === 'evaluador_pais' && isset($userData['pais_acceso']) && $userData['pais_acceso'] !== 'ALL') {
        $query .= " AND a.PAIS = '" . $userData['pais_acceso'] . "'";
    }
    
    $query .= ' ORDER BY a.TIENDA';

    $queryJobConfig = $this->bigQuery->query($query);
    $results = $this->bigQuery->runQuery($queryJobConfig);

    $tiendas = [];
    foreach ($results->rows() as $row) {
        $tiendas[] = $row['TIENDA'];
    }

    return $tiendas;
}

}