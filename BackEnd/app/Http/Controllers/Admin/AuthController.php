<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin()
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

        return view('admin.auth.login');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        // Validar entrada
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ], [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        $email = $request->email;
        $password = $request->password;

        // Rate limiting - prevenir ataques de fuerza bruta
        $key = 'login-attempts:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors([
                'email' => "Demasiados intentos de login. Intente de nuevo en {$seconds} segundos."
            ]);
        }

        try {
            // Buscar usuario por email
            $user = $this->usuario->findByEmail($email);

            if (!$user) {
                RateLimiter::hit($key, 300); // Bloquear por 5 minutos
                return back()->withErrors([
                    'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.'
                ]);
            }

            // Verificar contraseña
            if (!$this->usuario->verifyPassword($password, $user['password_hash'])) {
                RateLimiter::hit($key, 300);
                return back()->withErrors([
                    'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.'
                ]);
            }

            // Verificar que el usuario esté activo
            if (!$user['activo']) {
                return back()->withErrors([
                    'email' => 'Su cuenta está desactivada. Contacte al administrador.'
                ]);
            }

            // Login exitoso - limpiar rate limiting
            RateLimiter::clear($key);

            // Guardar usuario en sesión
            session([
                'admin_user' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'email' => $user['email'],
                    'rol' => $user['rol'],
                    'activo' => $user['activo'],
                    'pais_acceso' => $user['pais_acceso'] ?? 'ALL', // ✅ AGREGAR ESTA LÍNEA
                    'login_time' => now()->toISOString(),
                ]
            ]);

            // Regenerar ID de sesión por seguridad
            $request->session()->regenerate();

            // Redirigir al dashboard o URL intentada
            $intendedUrl = session('admin_intended_url');
            if ($intendedUrl) {
                session()->forget('admin_intended_url');
                return redirect($intendedUrl)->with('success', 'Bienvenido, ' . $user['nombre']);
            }

            return redirect()->route('admin.dashboard')->with('success', 'Bienvenido, ' . $user['nombre']);

        } catch (\Exception $e) {
            // Log del error para debugging
            Log::error('Error en login admin: ' . $e->getMessage());
            
            return back()->withErrors([
                'email' => 'Error interno del servidor. Intente nuevamente.'
            ]);
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        // Obtener datos del usuario antes de cerrar sesión
        $user = session('admin_user');
        
        // Limpiar sesión
        session()->forget('admin_user');
        session()->forget('admin_last_activity');
        session()->forget('admin_intended_url');
        session()->invalidate();
        session()->regenerateToken();

        // Mensaje de despedida
        $message = $user ? 'Sesión cerrada correctamente, ' . $user['nombre'] : 'Sesión cerrada correctamente';

        return redirect()->route('admin.login')->with('success', $message);
    }

    /**
     * Verificar estado de autenticación (para AJAX)
     */
    public function checkAuth()
    {
        if (session('admin_user')) {
            return response()->json([
                'authenticated' => true,
                'user' => session('admin_user')
            ]);
        }

        return response()->json([
            'authenticated' => false
        ], 401);
    }

    /**
     * Renovar sesión (para evitar timeout)
     */
    public function refreshSession(Request $request)
    {
        if (session('admin_user')) {
            // Actualizar tiempo de última actividad
            session(['admin_last_activity' => now()->toISOString()]);
            
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 401);
    }
}