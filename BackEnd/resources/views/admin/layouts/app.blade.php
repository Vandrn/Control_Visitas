<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Admin') - Control de Visitas ADOC</title>
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    @stack('styles')
    
    <!-- Tailwind CSS via CDN (TEMPORAL para testing) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- CSS personalizado para autocomplete -->
    <style>
        /* Autocomplete específico para tiendas */
        .autocomplete-dropdown {
            max-height: 240px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }

        .autocomplete-dropdown::-webkit-scrollbar {
            width: 6px;
        }

        .autocomplete-dropdown::-webkit-scrollbar-track {
            background: #f7fafc;
        }

        .autocomplete-dropdown::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }

        .autocomplete-dropdown::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        /* Highlight para búsquedas */
        mark {
            background-color: #fef08a !important;
            color: #365314 !important;
            font-weight: 600;
            padding: 0 1px;
            border-radius: 2px;
        }

        /* Responsive para móviles */
        @media (max-width: 640px) {
            .autocomplete-dropdown {
                max-height: 200px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div id="app" x-data="{ sidebarOpen: false }" class="min-h-screen">
        
<!-- Header -->
        @if(session('admin_user'))
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    
                    <!-- Logo y navegación móvil -->
                    <div class="flex items-center">
                        <button @click="sidebarOpen = !sidebarOpen" 
                                class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        
                        <div class="flex-shrink-0 flex items-center ml-2 md:ml-0">
                            <img class="h-8 w-auto" src="{{ asset('images/ADOC.png') }}" alt="ADOC">
                            <span class="ml-2 text-xl font-semibold text-gray-900 hidden sm:block">Admin Panel</span>
                        </div>
                    </div>

                    <!-- Navegación desktop -->
                    <nav class="hidden md:flex space-x-8">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="@if(request()->routeIs('admin.dashboard*')) text-blue-600 border-b-2 border-blue-600 @else text-gray-500 hover:text-gray-700 @endif px-3 py-2 text-sm font-medium">
                            Dashboard
                        </a>
                        <!-- Más enlaces de navegación aquí -->
                    </nav>

                    <!-- Usuario y logout -->
                    <div class="flex items-center space-x-4">
                        <div class="hidden sm:block">
    <span class="text-sm text-gray-700">{{ session('admin_user.nombre') }}</span>
    <div class="flex items-center space-x-2">
        <span class="text-xs text-gray-500">
            {{ session('admin_user.rol') === 'evaluador_pais' ? 'Evaluador País' : ucfirst(session('admin_user.rol')) }}
        </span>
        @if(session('admin_user.rol') === 'evaluador_pais' && session('admin_user.pais_acceso') !== 'ALL')
            <span class="text-xs bg-yellow-100 text-yellow-800 px-1 rounded">
                {{ session('admin_user.pais_acceso') }}
            </span>
        @endif
    </div>
</div>
                        
                        <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        @endif

        <!-- Contenido principal -->
        <main class="@if(session('admin_user')) py-6 @else py-0 @endif">
            <div class="@if(session('admin_user')) max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 @endif">
                
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

                <!-- Contenido de la página -->
                @yield('content')
            </div>
        </main>

        <!-- Sidebar móvil -->
        @if(session('admin_user'))
        <div x-show="sidebarOpen" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 md:hidden">
            
            <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="sidebarOpen = false"></div>
            
            <div class="relative flex-1 flex flex-col max-w-xs w-full bg-white">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button @click="sidebarOpen = false" 
                            class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
                    <nav class="mt-5 px-2 space-y-1">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="@if(request()->routeIs('admin.dashboard*')) bg-blue-100 text-blue-900 @else text-gray-600 hover:bg-gray-50 hover:text-gray-900 @endif group flex items-center px-2 py-2 text-base font-medium rounded-md">
                            Dashboard
                        </a>
                        <!-- Más enlaces aquí -->
                    </nav>
                </div>
            </div>
        </div>
        @endif
    </div>

    @stack('scripts')
</body>
</html>