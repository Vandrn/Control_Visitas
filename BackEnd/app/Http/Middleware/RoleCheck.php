<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class RoleCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role = null): Response
    {
        // Verificar autenticación primero
        if (!session('admin_user')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No autenticado'], 401);
            }
            return redirect()->route('admin.login');
        }

        $user = session('admin_user');

        // Si se especifica un rol, verificar que el usuario lo tenga
        if ($role && $user['rol'] !== $role) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Sin permisos'], 403);
            }
            
            abort(403, 'No tiene permisos para acceder a esta sección.');
        }
        
        // AGREGAR ESTAS L�0�1NEAS NUEVAS:
        // Validaci��n adicional para acceso por pa��s
        if ($user['rol'] === 'evaluador_pais') {
            $paisSolicitado = $request->route('pais') ?? $request->get('pais');
            
            if ($paisSolicitado && isset($user['pais_acceso']) && $user['pais_acceso'] !== 'ALL') {
                if ($user['pais_acceso'] !== $paisSolicitado) {
                    if ($request->expectsJson()) {
                        return response()->json(['error' => 'Sin acceso a este pa��s'], 403);
                    }
                    
                    abort(403, 'No tiene permisos para acceder a datos de este pa��s.');
                }
            }
        }

        // Log de acceso (opcional)
        Log::info('Admin access', [
            'user_id' => $user['id'],
            'user_email' => $user['email'],
            'role' => $user['rol'],
            'required_role' => $role,
            'route' => $request->route()->getName(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return $next($request);
    }
}