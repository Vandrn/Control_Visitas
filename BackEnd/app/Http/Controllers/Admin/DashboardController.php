<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    /**
     * Dashboard principal
     */
    public function index(Request $request)
    {
        try {
            // Obtener filtros de la sesión o request
            $filtros = $this->obtenerFiltros($request);

            // Obtener estadísticas generales (con filtros de país aplicados)
            // Obtener estadísticas generales (con filtros de país aplicados)
            $user = session('admin_user');
            // Obtener estadísticas generales
            $estadisticas = $this->usuario->getEstadisticasVisitas($filtros, $user);

            // Obtener visitas paginadas
            $page = $request->get('page', 1);
            $perPage = config('admin.pagination.per_page', 20);
            $visitas = $this->usuario->getVisitasPaginadas($filtros, $page, $perPage, $user);
            $totalVisitas = $this->usuario->contarVisitas($filtros, $user);
            $totalPages = ceil($totalVisitas / $perPage);

            // Obtener datos para filtros
            // Obtener datos para filtros (filtrados según permisos del usuario)
            $user = session('admin_user');
            $paises = $this->usuario->getPaisesDisponibles($user);
            $evaluadores = $this->usuario->getEvaluadoresDisponibles();

            // Preparar datos de paginación
            $paginacion = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalVisitas,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ];

            return view('admin.dashboard.index', compact(
                'estadisticas',
                'visitas',
                'paginacion',
                'filtros',
                'paises',
                'evaluadores'
            ));
        } catch (\Exception $e) {
            Log::error('Error en dashboard: ' . $e->getMessage());

            return view('admin.dashboard.index', [
                'estadisticas' => [],
                'visitas' => [],
                'paginacion' => ['current_page' => 1, 'per_page' => 20, 'total' => 0, 'total_pages' => 0],
                'filtros' => [],
                'paises' => [],
                'evaluadores' => [],
                'error' => 'Error al cargar los datos. Intente nuevamente.'
            ]);
        }
    }

    /**
     * API para obtener visitas (AJAX)
     */
    public function getVisitas(Request $request)
    {
        try {
            $user = session('admin_user');
            $filtros = $this->obtenerFiltros($request, $user);
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            $visitas = $this->usuario->getVisitasPaginadas($filtros, $page, $perPage, $user);
            $total = $this->usuario->contarVisitas($filtros, $user);

            return response()->json([
                'success' => true,
                'data' => $visitas,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error en getVisitas API: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener las visitas'
            ], 500);
        }
    }

    /**
     * Obtener y procesar filtros
     */
    private function obtenerFiltros(Request $request, $user = null)
    {
        $filtros = [];

        // AGREGAR ESTAS LÍNEAS AL INICIO:
        // Validar acceso por país para evaluador_pais
        if ($user && $user['rol'] === 'evaluador_pais' && isset($user['pais_acceso']) && $user['pais_acceso'] !== 'ALL') {
            // Forzar filtro por país asignado
            $filtros['pais'] = $user['pais_acceso'];

            // Si el usuario intenta filtrar por otro país, ignorar
            if ($request->has('pais') && $request->pais !== $user['pais_acceso']) {
                // Log de intento de acceso no autorizado
                Log::warning('Intento de acceso no autorizado', [
                    'user' => $user['email'],
                    'pais_solicitado' => $request->pais,
                    'pais_permitido' => $user['pais_acceso']
                ]);
            }
        }

        // Filtros de fechas
        if ($request->has('fecha_inicio') && $request->fecha_inicio) {
            $filtros['fecha_inicio'] = $request->fecha_inicio;
        }

        if ($request->has('fecha_fin') && $request->fecha_fin) {
            $filtros['fecha_fin'] = $request->fecha_fin;
        }

        // Si no hay fechas, usar últimos 30 días por defecto
        if (empty($filtros['fecha_inicio']) && empty($filtros['fecha_fin'])) {
            $filtros['fecha_inicio'] = Carbon::now()->subDays(30)->format('Y-m-d');
            $filtros['fecha_fin'] = Carbon::now()->format('Y-m-d');
        }

        // Filtro por país (para todos los roles excepto evaluador_pais restringido)
        if ($request->has('pais') && $request->pais) {
            // Solo aplicar si el usuario tiene permiso para ese país
            if (
                !$user || $user['rol'] !== 'evaluador_pais' ||
                !isset($user['pais_acceso']) ||
                $user['pais_acceso'] === 'ALL' ||
                $user['pais_acceso'] === $request->pais
            ) {
                $filtros['pais'] = $request->pais;
            }
        }

        // Otros filtros

        if ($request->has('tienda') && $request->tienda) {
            $filtros['tienda'] = $request->tienda;
        }

        if ($request->has('evaluador') && $request->evaluador) {
            $filtros['evaluador'] = $request->evaluador;
        }

        // Guardar filtros en sesión
        session(['admin_dashboard_filtros' => $filtros]);

        // Guardar filtros en sesión
        session(['admin_dashboard_filtros' => $filtros]);

        // DEBUGGING TEMPORAL - REMOVER DESPUÉS
        if (config('app.debug')) {
            Log::info('Filtros procesados', [
                'filtros' => $filtros,
                'request_pais' => $request->get('pais'),
                'request_tienda' => $request->get('tienda'),
                'user_rol' => $user['rol'] ?? 'no_user'
            ]);
        }

        return $filtros;
    }

    /**
     * Limpiar filtros
     */
    public function limpiarFiltros()
    {
        session()->forget('admin_dashboard_filtros');
        return redirect()->route('admin.dashboard');
    }

    /**
     * Obtener países permitidos según rol del usuario
     */
    public function getPaisesPermitidos()
    {
        try {
            $user = session('admin_user');
            $paises = $this->usuario->getPaisesDisponibles($user);

            return response()->json([
                'success' => true,
                'paises' => $paises,
                'restriccion' => $user['rol'] === 'evaluador_pais' ? $user['pais_acceso'] : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener países permitidos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener países'
            ], 500);
        }
    }

    /**
     * API para obtener lista de tiendas según permisos del usuario
     */
    public function getTiendasDisponibles(Request $request)
    {
        try {
            $user = session('admin_user');
            $tiendas = $this->usuario->getTiendasDisponibles($user);

            return response()->json([
                'success' => true,
                'tiendas' => $tiendas,
                'total' => count($tiendas)
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener tiendas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener las tiendas'
            ], 500);
        }
    }
}
