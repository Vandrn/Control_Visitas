<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Agregar middleware con alias para el admin
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.guest' => \App\Http\Middleware\AdminGuest::class,
            'admin.role' => \App\Http\Middleware\RoleCheck::class,
            'admin.pais' => \App\Http\Middleware\PaisAccess::class, // AGREGAR ESTA LÍNEA
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();