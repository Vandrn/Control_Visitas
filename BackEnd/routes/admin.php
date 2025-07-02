<?php

use Illuminate\Support\Facades\Route;

// Rutas específicas del módulo administrativo
Route::prefix('admin')->name('admin.')->group(function () {
    
    // Rutas que requieren autenticación
    Route::middleware(['admin.auth'])->group(function () {
        
        Route::get('/usuarios', [App\Http\Controllers\Admin\UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/reportes', [App\Http\Controllers\Admin\ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/configuracion', [App\Http\Controllers\Admin\ConfigController::class, 'index'])->name('config.index');
        
    });
    
});