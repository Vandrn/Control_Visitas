<?php

use App\Http\Controllers\FormularioController;
use Illuminate\Support\Facades\Route;

// Guardar secciones
Route::post('retail/guardar-seccion', [FormularioController::class, 'guardarSeccion'])->name('guardar.seccion');

// Formulario viejo (todavía lo dejo por compatibilidad)
Route::get('/formulario', [FormularioController::class, 'mostrarFormulario'])->name('formulario');
Route::post('/formulario', [FormularioController::class, 'mostrarFormulario'])->name('formulario');

// Mantener sesión activa
Route::get('/keep-alive', fn() => response()->json(['alive' => true]));

// -----------------------------
// 🔥 NUEVO WIZARD REAL
// -----------------------------
Route::prefix('visita')->group(function () {

    // INTRO PRINCIPAL
    Route::get('/', fn() => view('visita.intro'))->name('visita.intro');

    // DATOS
    Route::get('/datos', fn() => view('visita.datos'))->name('visita.datos');

    // SECCIÓN 1
    Route::get('/seccion-1', fn() => view('visita.seccion1'))->name('visita.seccion1');

    // ---------------------------
    // OPERACIONES
    // ---------------------------
    Route::get('/operaciones', fn() => view('visita.operaciones_intro'))
        ->name('visita.operaciones.intro');

    Route::get('/operaciones/preguntas', function () {

        $preguntasOperaciones = [
            "Pintura de tienda en buen estado. Interior/Exterior.",
            "Vitrinas de tiendas limpias, con iluminación y acrílicos en buen estado.",
            "Exhibición de producto en vitrina según estándares.",
            "Sala de ventas limpia, ordenada y con iluminación en buen estado.",
            "Aires acondicionados/ventiladores y escaleras en buen estado.",
            "Repisas, mesas y muebles de exhibición limpios y en buen estado.",
            "Mueble de caja limpio, ordenado y en buen estado",
            "Equipo funcionando (radio, tel., cel., conteo de clientes, eq. de computo).",
            "Utilización de la radio ADOC para ambientar la tienda.",
            "Bodega limpia, con iluminación en buen estado y ordenada según manual.",
            "Accesorios de limpieza ordenados y ubicados en el lugar adecuado.",
            "Área de comida limpia y artículos personales ordenados en su área.",
            "Baño limpio y ordenado",
            "La tienda cuenta con suficientes sillas o bancos para cliente según layout.",
            "Las cajas alzadoras de zapatos se usan en las exhibiciones.",
            "No se usa cinta adhesiva (tape) en ningún lugar de la tienda.",
            "No hay muebles dañados, rotos o quebrados.",
            "El área de caja está ordenada y conforme a estándares autorizados.",
            "Se ofrecen accesorios a los clientes siempre.",
            "Luces funcionales en muebles de pared y mesa.",
            "Pantallas en vitrina posicionadas verticales (90 grados).",
            "Azulejos, fórmica y piso en buen estado.",
            "Observaciones del área de operaciones"
        ];

        // CONFIGURACIÓN ESPECIAL DE ESTA SECCIÓN
        $preguntasConImagen = [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 21, 22];
        $preguntasNoAplica = []; // operaciones NO usa NA

        return view('visita.operaciones_preguntas', compact(
            'preguntasOperaciones',
            'preguntasConImagen',
            'preguntasNoAplica'
        ));
    })
        ->name('visita.operaciones.preguntas');

    // ---------------------------
    // ADMINISTRACIÓN

    Route::get('/administracion', fn() => view('visita.administracion_intro'))
        ->name('visita.administracion.intro');

    Route::get('/administracion/preguntas', function () {

        $preguntasAdministracion = [
            "Cuenta de orden al día.",
            "Documentos de transferencias y envíos ingresados al sistema al día",
            "Remesas de efectivo al día e ingresados al sistema",
            "Libro de cuadre de efectivo y caja chica al día",
            "Libro de horarios al día y firmados por los empleados",
            "Conteo efectuados según lineamientos establecidos.",
            "Files actualizados.",
            "Observaciones del área de administración."
        ];

        return view('visita.administracion_preguntas', compact('preguntasAdministracion'));
    })->name('visita.administracion.preguntas');
});


// AJAX dinámicos
Route::get('retail/paises', [FormularioController::class, 'obtenerPaises']);
Route::get('retail/zonas/{pais}', [FormularioController::class, 'obtenerZonas']);
Route::get('retail/tiendas/{pais}/{zona}', [FormularioController::class, 'obtenerTiendas']);

// Subida incremental
Route::post('retail/subir-imagen-incremental', [FormularioController::class, 'subirImagenIncremental']);
