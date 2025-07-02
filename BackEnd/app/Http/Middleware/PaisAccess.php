<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Usuario;
use Symfony\Component\HttpFoundation\Response;

class PaisAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $pais = null): Response
    {
        $user = session('admin_user');
        
        if (!$user) {
            return redirect()->route('admin.login');
        }
        
        // Admin y evaluador normal tienen acceso completo
        if (in_array($user['rol'], ['admin', 'evaluador'])) {
            return $next($request);
        }
        
        // Para evaluador_pais, verificar acceso específico
        if ($user['rol'] === 'evaluador_pais') {
            $paisAcceso = $user['pais_acceso'] ?? null;
            
            // Si no hay restricción específica de país en la ruta, permitir
            if (!$pais) {
                return $next($request);
            }
            
            // Verificar acceso al país específico
            if ($paisAcceso === 'ALL' || $paisAcceso === $pais) {
                return $next($request);
            }
            
            abort(403, "No tiene acceso a datos del país: {$pais}");
        }
        
        return $next($request);
    }
}