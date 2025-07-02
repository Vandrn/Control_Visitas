<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminGuest
{
    /**
     * Handle an incoming request.
     * Previene que usuarios autenticados accedan a páginas de guest (login)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si ya está autenticado, redirigir al dashboard
        if (session('admin_user')) {
            // Verificar si había una URL intentada
            $intendedUrl = session('admin_intended_url');
            
            if ($intendedUrl) {
                session()->forget('admin_intended_url');
                return redirect($intendedUrl);
            }
            
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}