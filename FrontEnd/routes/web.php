<?php

use App\Http\Controllers\FormularioController;

use Illuminate\Support\Facades\Route;

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::post('retail/guardar-seccion', [FormularioController::class, 'guardarSeccion'])->name('guardar.seccion');
Route::get('/formulario', [FormularioController::class, 'mostrarFormulario'])->name('formulario');
Route::post('/formulario', [FormularioController::class, 'mostrarFormulario'])->name('formulario');
Route::get('retail/keep-alive', function () {
    return response()->json(['alive' => true]);
});

//datos dinamicos AJAX
Route::get('retail/paises', [FormularioController::class, 'obtenerPaises']);
Route::get('retail/zonas/{pais}', [FormularioController::class, 'obtenerZonas']);
Route::get('retail/tiendas/{pais}/{zona}', [FormularioController::class, 'obtenerTiendas']);

Route::post('retail/subir-imagen-incremental', [FormularioController::class, 'subirImagenIncremental']);