<?php

use App\Http\Controllers\FormularioController;

use Illuminate\Support\Facades\Route;

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::get('/formulario', [FormularioController::class, 'mostrarFormulario'])->name('formulario');
Route::post('/formulario', [FormularioController::class, 'mostrarFormulario'])->name('formulario');

//guardar progreso
Route::get('/retail/form/progreso/{sessionId}', [FormularioController::class, 'obtenerProgreso']);
Route::post('/retail/form/progreso/{sessionId}', [FormularioController::class, 'guardarProgreso']);

// 🆕 NUEVOS ENDPOINTS POR SECCIÓN
Route::post('retail/save-datos', [FormularioController::class, 'saveDatos'])->name('save.datos');
Route::post('retail/save-seccion', [FormularioController::class, 'saveSeccionIndividual'])->name('save.seccion');
Route::post('retail/save-main-fields', [FormularioController::class, 'saveMainFields'])->name('save.main.fields');
Route::post('retail/save-kpis', [FormularioController::class, 'saveKPIs'])->name('save.kpis');
Route::post('retail/save-planes', [FormularioController::class, 'savePlanes'])->name('save.planes');

Route::get('/retail/keep-alive', function () {
    return response()->json(['alive' => true]);
});

//datos dinamicos AJAX
Route::get('retail/paises', [FormularioController::class, 'obtenerPaises']);
Route::get('retail/zonas/{pais}', [FormularioController::class, 'obtenerZonas']);
Route::get('retail/tiendas/{pais}/{zona}', [FormularioController::class, 'obtenerTiendas']);

Route::post('retail/subir-imagen-incremental', [FormularioController::class, 'subirImagenIncremental']);