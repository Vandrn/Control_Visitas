<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (!session('admin_user')) {
            // Si es una petición AJAX, devolver error JSON
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            
            // Guardar la URL intentada para redirección posterior
            session(['admin_intended_url' => $request->fullUrl()]);
            
            return redirect()->route('admin.login')->with('error', 'Debe iniciar sesión para acceder.');
        }

        $user = session('admin_user');

        // Verificar si el usuario está activo
        if (!$user['activo']) {
            session()->forget('admin_user');
            return redirect()->route('admin.login')->with('error', 'Su cuenta está desactivada.');
        }

        // Verificar timeout de sesión (opcional)
        $sessionTimeout = config('admin.security.session_timeout', 120); // minutos
        $lastActivity = session('admin_last_activity');
        
        if ($lastActivity) {
            $timeoutTime = now()->subMinutes($sessionTimeout);
            if (strtotime($lastActivity) < $timeoutTime->timestamp) {
                session()->forget('admin_user');
                session()->forget('admin_last_activity');
                
                return redirect()->route('admin.login')->with('error', 'Su sesión ha expirado por inactividad.');
            }
        }

        // Verificar si el usuario tiene el campo pais_acceso (para compatibilidad)
        if (!isset($user['pais_acceso'])) {
            // Usuario sin pais_acceso definido, asignar ALL por defecto
            $user['pais_acceso'] = 'ALL';
            session(['admin_user' => $user]);
        }

        return $next($request);
    }
}