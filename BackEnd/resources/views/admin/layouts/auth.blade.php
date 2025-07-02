<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Iniciar Sesión') - Control de Visitas ADOC</title>
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        
        <!-- Header con logo -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <img class="h-12 w-auto" src="{{ asset('images/ADOC.png') }}" alt="ADOC">
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                @yield('heading', 'Panel Administrativo')
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                @yield('subheading', 'Control de Visitas a Tiendas')
            </p>
        </div>

        <!-- Formulario -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                
                <!-- Mensajes de éxito/error -->
                @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
                @endif

                <!-- Contenido del formulario -->
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} ADOC. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>