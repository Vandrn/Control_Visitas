<?php

namespace App\Models;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class Usuario
{
    protected $bigQuery;

    protected $table = 'usuarios';
    protected $dataset = 'OPB';
    protected $projectId = 'adoc-bi-dev';

    // Tabla principal de visitas
    protected $visitasTable = 'GR_pruebas';


    public function __construct()
    {
        $this->bigQuery = new BigQueryClient([
            'projectId' => config('admin.bigquery.project_id'),
            'keyFilePath' => storage_path('app' . config('admin.bigquery.key_file')),
        ]);
    
        $this->visitasTable = 'GR_pruebas';
    
        Log::info('✅ Tabla de visitas usada: ' . $this->visitasTable);
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

        $params = [];
        $whereClause = $this->buildWhereClause($filtros, $userData, $params);

        $query = sprintf(
            'SELECT 
            COUNT(*) as total_visitas,
            COUNT(DISTINCT pais) as total_paises,
            COUNT(DISTINCT zona) as total_zonas,
            COUNT(DISTINCT tienda) as total_tiendas,
            COUNT(DISTINCT correo_realizo) as total_evaluadores,
            DATE(MIN(fecha_hora_inicio)) as fecha_primera_visita,
            DATE(MAX(fecha_hora_fin)) as fecha_ultima_visita
        FROM `%s.%s.%s` %s',
            $this->projectId,
            $this->dataset,
            $this->visitasTable,
            $whereClause
        );

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters($params);
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
     * Obtener visitas con paginación y filtros
     */
    public function getVisitasPaginadas($filtros = [], $page = 1, $perPage = 20, $userData = null)
    {
        $params = [];
        $whereClause = $this->buildWhereClause($filtros, $userData, $params);
        $offset = ($page - 1) * $perPage;

        $query = sprintf(
            'SELECT 
            v.id,
            v.fecha_hora_inicio,
            v.correo_realizo,
            v.lider_zona,
            v.pais,
            v.zona,
            v.tienda,
            v.ubicacion,

            -- Promedio general de todas las respuestas numéricas
            (
              SELECT AVG(CAST(p.respuesta AS FLOAT64))
              FROM UNNEST(v.secciones) AS s,
                   UNNEST(s.preguntas) AS p
              WHERE SAFE_CAST(p.respuesta AS FLOAT64) IS NOT NULL
            ) AS puntuacion_general,

            -- Promedio por sección específica
            (
              SELECT AVG(CAST(p.respuesta AS FLOAT64))
              FROM UNNEST(v.secciones) AS s,
                   UNNEST(s.preguntas) AS p
              WHERE s.nombre_seccion = "Operaciones" AND SAFE_CAST(p.respuesta AS FLOAT64) IS NOT NULL
            ) AS puntuacion_operaciones,

            (
              SELECT AVG(CAST(p.respuesta AS FLOAT64))
              FROM UNNEST(v.secciones) AS s,
                   UNNEST(s.preguntas) AS p
              WHERE s.nombre_seccion = "Administración" AND SAFE_CAST(p.respuesta AS FLOAT64) IS NOT NULL
            ) AS puntuacion_administracion,

            (
              SELECT AVG(CAST(p.respuesta AS FLOAT64))
              FROM UNNEST(v.secciones) AS s,
                   UNNEST(s.preguntas) AS p
              WHERE s.nombre_seccion = "Producto" AND SAFE_CAST(p.respuesta AS FLOAT64) IS NOT NULL
            ) AS puntuacion_producto,

            (
              SELECT AVG(CAST(p.respuesta AS FLOAT64))
              FROM UNNEST(v.secciones) AS s,
                   UNNEST(s.preguntas) AS p
              WHERE s.nombre_seccion = "Personal" AND SAFE_CAST(p.respuesta AS FLOAT64) IS NOT NULL
            ) AS puntuacion_personal,

            (
              SELECT AVG(CAST(p.respuesta AS FLOAT64))
              FROM UNNEST(v.secciones) AS s,
                   UNNEST(s.preguntas) AS p
              WHERE s.nombre_seccion = "KPIs" AND SAFE_CAST(p.respuesta AS FLOAT64) IS NOT NULL
            ) AS puntuacion_kpis

        FROM `%s.%s.%s` v
        %s
        ORDER BY v.fecha_hora_inicio DESC
        LIMIT %d OFFSET %d',
            $this->projectId,
            $this->dataset,
            $this->visitasTable,
            $whereClause,
            $perPage,
            $offset
        );

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters($params);
        $results = $this->bigQuery->runQuery($queryJobConfig);

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
            'SELECT DISTINCT pais FROM `%s.%s.%s` WHERE pais IS NOT NULL',
            $this->projectId,
            $this->dataset,
            $this->visitasTable
        );

        $params = [];
        // Si es evaluador_pais, filtrar solo su país
        if (
            $userData && $userData['rol'] === 'evaluador_pais' &&
            isset($userData['pais_acceso']) && $userData['pais_acceso'] !== 'ALL'
        ) {
            $query .= ' AND pais = @pais_acceso';
            $params['pais_acceso'] = $this->sanitize($userData['pais_acceso']);
        }

        $query .= ' ORDER BY pais';

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters($params);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $paises = [];
        foreach ($results->rows() as $row) {
            $paises[] = $row['pais'];
        }

        return $paises;
    }

    /**
     * Obtener evaluadores disponibles
     */
    public function getEvaluadoresDisponibles()
    {
        $query = sprintf(
            'SELECT DISTINCT correo_realizo, lider_zona
            FROM `%s.%s.%s`
            WHERE correo_realizo IS NOT NULL
            ORDER BY correo_realizo',
            $this->projectId,
            $this->dataset,
            $this->visitasTable
        );

        $queryJobConfig = $this->bigQuery->query($query);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $evaluadores = [];
        foreach ($results->rows() as $row) {
            $evaluadores[] = [
                'email' => $row['correo_realizo'],
                'nombre' => $row['lider_zona'] ?? $row['correo_realizo']
            ];
        }

        return $evaluadores;
    }

    /**
     * Construir cláusula WHERE basada en filtros
     */
    private function buildWhereClause($filtros = [], $userData = null, array &$params = [])
    {
        $conditions = [];

        if (!$userData) {
            $userData = session('admin_user');
        }

        // Filtro por rol de usuario
        if ($userData && $userData['rol'] === 'evaluador') {
            $conditions[] = 'correo_realizo = @evaluador_email';
            $params['evaluador_email'] = $this->sanitize($userData['email']);
        }

        // Filtro automático por país para evaluador_pais
        if (
            $userData &&
            $userData['rol'] === 'evaluador_pais' &&
            isset($userData['pais_acceso']) &&
            $userData['pais_acceso'] !== 'ALL'
        ) {
            $conditions[] = 'pais = @pais_acceso';
            $params['pais_acceso'] = $this->sanitize($userData['pais_acceso']);
        }

        // Filtros manuales
        if (!empty($filtros['fecha_inicio']) && $this->validarFecha($filtros['fecha_inicio'])) {
            $conditions[] = 'DATE(fecha_hora_inicio) >= @fecha_inicio';
            $params['fecha_inicio'] = $filtros['fecha_inicio'];
        }
        if (!empty($filtros['fecha_fin']) && $this->validarFecha($filtros['fecha_fin'])) {
            $conditions[] = 'DATE(fecha_hora_inicio) <= @fecha_fin';
            $params['fecha_fin'] = $filtros['fecha_fin'];
        }
        if (!empty($filtros['pais'])) {
            $conditions[] = 'pais = @pais';
            $params['pais'] = $this->sanitize($filtros['pais']);
        }
        if (!empty($filtros['tienda'])) {
            $conditions[] = 'tienda LIKE @tienda';
            $params['tienda'] = '%' . $this->sanitize($filtros['tienda']) . '%';
        }
        if (!empty($filtros['evaluador'])) {
            $conditions[] = 'correo_realizo = @evaluador';
            $params['evaluador'] = $this->sanitize($filtros['evaluador']);
        }

        return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }

    /**
     * Validar formato de fecha (YYYY-MM-DD)
     */
    private function validarFecha($fecha)
    {
        return is_string($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
    }

    /**
     * Sanitizar texto básico
     */
    private function sanitize($valor)
    {
        return is_string($valor) ? trim(strip_tags($valor)) : $valor;
    }

    /**
     * Contar total de visitas con filtros
     */
    public function contarVisitas($filtros = [], $userData = null)
    {
        $params = [];
        $whereClause = $this->buildWhereClause($filtros, $userData, $params); // ✅ Pasar userData

        $query = sprintf(
            'SELECT COUNT(*) as total FROM `%s.%s.%s` %s',
            $this->projectId,
            $this->dataset,
            $this->visitasTable,
            $whereClause
        );

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters($params);
        $results = $this->bigQuery->runQuery($queryJobConfig);

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
            'SELECT concat(gr.pais,left(gr.tienda,3)) BV_PAIS_TIENDA, a.GEO as TIENDA_COORDENADAS, gr.*
    FROM `%s.%s.%s` gr
    LEFT JOIN (
        SELECT concat(dsm.LATITUD,",",replace(dsm.LONGITUD,"\'","")) GEO, dsm.PAIS_TIENDA
        FROM `%s.bi_lab.dim_store_master` dsm
        WHERE dsm.LATITUD NOT IN ("nan")
    ) a ON concat(gr.pais,left(gr.tienda,3)) = a.PAIS_TIENDA
    WHERE gr.id = @id',
            $this->projectId,
            $this->dataset,
            $this->visitasTable,
            $this->projectId
        );
        // Si es evaluador, verificar que solo vea sus visitas
        if ($userRole === 'evaluador' && $userEmail) {
            $query .= ' AND correo_realizo = @user_email';
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
     * Obtener URLs de imágenes de una visita
     */
    public function getImagenesVisita($id, $userRole = null, $userEmail = null)
    {
        $visita = $this->getVisitaCompleta($id, $userRole, $userEmail);

        if (!$visita || !isset($visita['secciones'])) {
            return [];
        }

        $imagenes = [];

        foreach ($visita['secciones'] as $seccion) {
            $nombre = $seccion['nombre_seccion'] ?? 'Sin sección';

            $imagenes[$nombre] = [
                'titulo' => $nombre,
                'imagenes' => [],
            ];

            foreach ($seccion['preguntas'] as $pregunta) {
                if (isset($pregunta['imagenes']) && is_array($pregunta['imagenes']) && count($pregunta['imagenes']) > 0) {
                    foreach ($pregunta['imagenes'] as $img) {
                        $imagenes[$nombre]['imagenes'][] = $img;
                    }
                }
            }

            // Eliminar la sección si no tiene imágenes
            if (empty($imagenes[$nombre]['imagenes'])) {
                unset($imagenes[$nombre]);
            }
        }

        return $imagenes;
    }


    /**
     * Procesar y estructurar datos de la visita para display
     */
    public function procesarDatosVisita($visita)
    {
        if (!$visita || !isset($visita['secciones'])) {
            return null;
        }

        $resultado = [
            'id' => $visita['id'],
            'fecha_hora_inicio' => $visita['fecha_hora_inicio'],
            'fecha_hora_fin' => $visita['fecha_hora_fin'] ?? null,
            'correo_realizo' => $visita['correo_realizo'],
            'lider_zona' => $visita['lider_zona'],
            'pais' => $visita['pais'],
            'zona' => $visita['zona'],
            'tienda' => $visita['tienda'],
            'ubicacion' => $visita['ubicacion'],
            'secciones' => []
        ];

        // Reordenar secciones por nombre y también como array para tarjetas
        foreach ($visita['secciones'] as $seccion) {
            $nombre = $seccion['nombre_seccion'];
            $preguntas = [];

            foreach ($seccion['preguntas'] as $pregunta) {
                $codigo = $pregunta['codigo_pregunta'];
                $preguntas[$codigo] = [
                    'codigo_pregunta' => $codigo,
                    'respuesta' => $pregunta['respuesta'],
                    'imagenes' => $pregunta['imagenes'] ?? []
                ];
            }

            // Agrupado por nombre de sección (acceso rápido por nombre)
            $resultado[$nombre] = [
                'preguntas' => $preguntas
            ];

            // También como array para las tarjetas y el detalle
            $resultado['secciones'][] = [
                'nombre_seccion' => $nombre,
                'preguntas' => array_values($preguntas) // Para que sea un array indexado y el foreach del Blade funcione
            ];
        }

        // Reorganizar KPIs (si los usás)
        if (isset($visita['kpis'])) {
            $kpiPreguntas = [];
            foreach ($visita['kpis'] as $kpi) {
                $kpiPreguntas[$kpi['codigo_pregunta']] = $kpi['valor'];
            }
            $resultado['kpis'] = [
                'preguntas' => $kpiPreguntas
            ];
        }

        // Reorganizar planes de acción
        if (isset($visita['planes'])) {
            foreach ($visita['planes'] as $plan) {
                $resultado['planes_accion'][] = [
                    'descripcion' => $plan['descripcion'],
                    'fecha' => $plan['fecha_cumplimiento']
                ];
            }
        }

        return $resultado;
    }

    /**
     * Calcular puntuaciones por sección
     */
    public function calcularPuntuaciones($visita)
    {
        $secciones = ['operaciones', 'administracion', 'producto', 'personal'];

        $resultados = [];

        foreach ($secciones as $seccion) {
            if (!isset($visita[$seccion]['preguntas']) || empty($visita[$seccion]['preguntas'])) {
                continue;
            }

            $preguntas = $visita[$seccion]['preguntas'];
            $suma = 0;
            $conteo = 0;

            foreach ($preguntas as $pregunta) {
                $respuesta = $pregunta['respuesta'] ?? null;
                if (is_numeric($respuesta)) {
                    $suma += floatval($respuesta); // ya deberían venir normalizadas (0.2 a 1)
                    $conteo++;
                }
            }

            if ($conteo > 0) {
                $promedio = $suma / $conteo;
                $resultados[$seccion] = [
                    'promedio' => $promedio,
                    'porcentaje' => $promedio * 100,
                    'estrellas' => $promedio * 5,
                    'total_preguntas' => $conteo
                ];
            }
        }

        return $resultados;
    }

    /**
     * Calcular puntajes por área para mostrar visualmente
     */
    public function calcularPuntajesPorArea($visita)
    {
        $resultados = [];

        foreach ($visita['secciones'] as $nombre => $preguntas) {
            $suma = 0;
            $total = 0;

            foreach ($preguntas as $codigo => $data) {
                if (isset($data['respuesta']) && is_numeric($data['respuesta'])) {
                    $suma += floatval($data['respuesta']);
                    $total++;
                }
            }

            if ($total > 0) {
                $promedio = round($suma / $total, 2);
                $porcentaje = round(($promedio / 5) * 100, 1);
                $estado = $porcentaje >= 80 ? 'Bueno' : ($porcentaje >= 60 ? 'Regular' : 'Bajo');

                $resultados[$nombre] = [
                    'promedio' => $promedio,
                    'porcentaje' => $porcentaje,
                    'estado' => $estado,
                    'total_preguntas' => $total,
                ];
            }
        }

        return $resultados;
    }

    /**
     * 📍 Calcular distancia entre dos coordenadas usando fórmula Haversine
     */
    public function calcularDistancia($lat1, $lng1, $lat2, $lng2)
    {
        $radioTierra = 6371000; // Radio de la Tierra en metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $radioTierra * $c; // Distancia en metros
    }

    /**
     * 📍 Validar distancia de una visita
     */
    public function validarDistanciaVisita($visitaData)
    {
        // Extraer ubicación del usuario y de la tienda
        $ubicacionUsuario = $visitaData['ubicacion'] ?? null;
        $coordenadasTienda = $visitaData['TIENDA_COORDENADAS'] ?? null;

        // Siempre parsear coordenadas de tienda si existen
        $coordsTienda = null;
        if ($coordenadasTienda) {
            $parts = explode(',', $coordenadasTienda);
            if (count($parts) === 2) {
                $coordsTienda = [
                    'lat' => floatval(trim($parts[0])),
                    'lng' => floatval(trim($parts[1])),
                ];
            }
        }

        // Si falta usuario o tienda => retornar error, pero incluir tienda
        if (!$ubicacionUsuario || !$coordenadasTienda || !$coordsTienda) {
            return [
                'valida'        => false,
                'distancia'     => null,
                'mensaje'       => '⚠️ No se encontraron coordenadas de usuario para validar',
                'estado'        => 'sin_datos',
                'coords_usuario'=> null,
                'coords_tienda' => $coordsTienda, // <- siempre se manda
            ];
        }

        // Parsear coordenadas de usuario
        $coordsUsuario = explode(',', $ubicacionUsuario);
        if (count($coordsUsuario) !== 2) {
            return [
                'valida'        => false,
                'distancia'     => null,
                'mensaje'       => '⚠️ Formato de ubicación de usuario inválido',
                'estado'        => 'error',
                'coords_usuario'=> null,
                'coords_tienda' => $coordsTienda,
            ];
        }

        $latUsuario = floatval(trim($coordsUsuario[0]));
        $lngUsuario = floatval(trim($coordsUsuario[1]));
        $latTienda  = $coordsTienda['lat'];
        $lngTienda  = $coordsTienda['lng'];

        // Calcular distancia
        $distancia = $this->calcularDistancia($latUsuario, $lngUsuario, $latTienda, $lngTienda);
        $distanciaRedondeada = round($distancia);

        $esValida = $distanciaRedondeada <= 50;

        return [
            'valida'        => $esValida,
            'distancia'     => $distanciaRedondeada,
            'mensaje'       => $esValida
                ? "✅ Visita válida: {$distanciaRedondeada} metros de la tienda"
                : "❌ Visita sospechosa: {$distanciaRedondeada} metros de la tienda (muy lejos)",
            'estado'        => $esValida ? 'valida' : 'invalida',
            'coords_usuario'=> ['lat' => $latUsuario, 'lng' => $lngUsuario],
            'coords_tienda' => $coordsTienda, // <- garantizado siempre
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
            'SELECT DISTINCT a.tienda FROM `%s.%s.%s` a WHERE a.tienda IS NOT NULL',
            $this->projectId,
            $this->dataset,
            $this->visitasTable
        );

        $params = [];
        // Si es evaluador_pais, filtrar solo su país
        if (
            $userData && $userData['rol'] === 'evaluador_pais' &&
            isset($userData['pais_acceso']) && $userData['pais_acceso'] !== 'ALL'
        ) {
            $query .= ' AND a.pais = @pais_acceso';
            $params['pais_acceso'] = $this->sanitize($userData['pais_acceso']);
        }

        $query .= ' ORDER BY a.tienda';

        $queryJobConfig = $this->bigQuery->query($query)
            ->parameters($params);
        $results = $this->bigQuery->runQuery($queryJobConfig);

        $tiendas = [];
        foreach ($results->rows() as $row) {
            $tiendas[] = $row['tienda'];
        }

        return $tiendas;
    }
}
