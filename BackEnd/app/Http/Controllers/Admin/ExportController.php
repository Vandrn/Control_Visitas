<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function exportExcel(Request $request)
    {
        // Verificar que el usuario sea admin
        $user = session('admin_user');
        
        if ($user['rol'] !== 'admin') {
            return response()->json(['error' => 'Sin permisos de administrador'], 403);
        }

        return response()->json([
            'message' => 'Exportación Excel funcionando',
            'user' => $user['nombre'],
            'role' => $user['rol']
        ]);
    }
}