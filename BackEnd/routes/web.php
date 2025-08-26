<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\VisitaController;
use App\Http\Controllers\Admin\ExportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Todas las rutas web para el dashboard administrativo.
| Accesibles desde https://adocencuesta.com/retail-dashboard/
*/

// ===============================================
// RUTAS PÚBLICAS (sin autenticación)
// ===============================================

// Redirección de raíz para usuarios admin
Route::get('/', function () {
    if (session('admin_user')) {
        return redirect('/dashboard');
    }
    return redirect('/login');
});

// Ruta alternativa sin nombre para evitar conflictos
Route::get('/home', function () {
    if (session('admin_user')) {
        return redirect('/dashboard');
    }
    return redirect('/login');
})->name('admin.home');

// ===============================================
// RUTAS DE AUTENTICACIÓN
// ===============================================

// Rutas de login (solo para usuarios NO autenticados)
Route::middleware(['admin.guest'])->group(function () {
    /** Login: muestra formulario **/
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    /** Login: procesa credenciales **/
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.post');
});

// ===============================================
// RUTAS PROTEGIDAS (requieren autenticación)
// ===============================================

Route::middleware(['admin.auth'])->group(function () {
    
    /** Dashboard principal **/
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    
    /** Logout **/
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    
    /** Detalle de visita individual **/
    Route::get('/admin/visita/{id}', [VisitaController::class, 'show'])->name('admin.visita.show');
    
    /** Galería de imágenes de una visita **/
    Route::get('/admin/visita/{id}/imagenes', [VisitaController::class, 'imagenes'])->name('admin.visita.imagenes');

    /** Detalle de área específica dentro de una visita **/
    Route::get('/admin/visita/{id}/area/{seccion}', [VisitaController::class, 'detalleArea'])->name('detalle.area');
    
    /** API interna para tabla de visitas con filtros AJAX **/
    Route::get('/admin/api/visitas', [DashboardController::class, 'getVisitas'])->name('admin.api.visitas');
    
    /** API para obtener tiendas disponibles **/
    Route::get('/admin/api/tiendas', [DashboardController::class, 'getTiendasDisponibles'])->name('admin.api.tiendas');
    
    /** APIs de utilidad para autenticación **/
    Route::get('/admin/api/auth/check', [AuthController::class, 'checkAuth'])->name('admin.api.auth.check');
    Route::post('/admin/api/auth/refresh', [AuthController::class, 'refreshSession'])->name('admin.api.auth.refresh');
    
    /** Exportación Excel (solo para administradores) **/
    Route::middleware(['admin.role:admin'])->group(function () {
        Route::get('/admin/export/excel', [ExportController::class, 'exportExcel'])->name('admin.export.excel');
    });
    
});

// ===============================================
// RUTAS DE DEBUGGING (quitar en producción)
// ===============================================

if (config('app.debug')) {
    Route::get('/test-route', function () {
        return response()->json([
            'status' => 'Laravel funcionando correctamente',
            'timestamp' => now()->toISOString(),
            'user_authenticated' => session('admin_user') ? true : false,
            'user_data' => session('admin_user') ? session('admin_user') : null,
            'session_id' => session()->getId(),
            'url' => request()->url(),
            'method' => request()->method()
        ]);
    })->name('admin.test');

    // Ruta para verificar conexión BigQuery
    Route::get('/test-bigquery', function () {
        try {
            $usuario = new \App\Models\Usuario();
            $estadisticas = $usuario->getEstadisticasVisitas();
            return response()->json([
                'status' => 'BigQuery conectado correctamente',
                'estadisticas' => $estadisticas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'Error en BigQuery',
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('admin.test.bigquery');

    // ===============================================
    // RUTAS TEMPORALES PARA LIMPIAR CACHÉ (ELIMINAR DESPUÉS)
    // ===============================================

    Route::get('/clear-cache', function() {
        try {
            $results = [];
        
        // Limpiar caché de rutas
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            unlink(base_path('bootstrap/cache/routes-v7.php'));
            $results[] = 'Routes cache cleared';
        }
        
        // Limpiar caché de configuración
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            unlink(base_path('bootstrap/cache/config.php'));
            $results[] = 'Config cache cleared';
        }
        
        // Limpiar caché de vistas
        $viewPath = storage_path('framework/views');
        if (is_dir($viewPath)) {
            $files = glob($viewPath . '/*');
            foreach($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $results[] = 'Views cache cleared';
        }
        
        // Limpiar caché de aplicación
        $cachePath = storage_path('framework/cache/data');
        if (is_dir($cachePath)) {
            $files = glob($cachePath . '/*');
            foreach($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $results[] = 'Application cache cleared';
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cache cleared successfully',
            'details' => $results,
            'timestamp' => now()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/optimize-cache', function() {
    try {
        // Crear caché de configuración
        $config = app('config');
        $configPath = base_path('bootstrap/cache/config.php');
        file_put_contents($configPath, '<?php return ' . var_export($config->all(), true) . ';');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Cache optimized for production',
            'timestamp' => now()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/check-status', function() {
    return response()->json([
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'environment' => app()->environment(),
        'debug_mode' => config('app.debug'),
        'app_url' => config('app.url'),
        'asset_url' => config('app.asset_url'),
        'routes_cached' => file_exists(base_path('bootstrap/cache/routes-v7.php')),
        'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
        'session_driver' => config('session.driver'),
        'cache_driver' => config('cache.default'),
        'user_session' => session('admin_user') ? 'Authenticated' : 'Not authenticated',
        'timestamp' => now()
    ]);
});

Route::get('/generate-key', function() {
    $key = 'base64:' . base64_encode(random_bytes(32));
    return response()->json([
        'key' => $key,
        'instruction' => 'Copia esta clave y ponla en tu archivo .env como APP_KEY=' . $key
    ]);
});
}


