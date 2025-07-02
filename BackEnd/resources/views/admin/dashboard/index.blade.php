@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="dashboardComponent()" class="space-y-6">
    
    <!-- Header del Dashboard -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Administrativo</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Bienvenido, {{ session('admin_user.nombre') }} 
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                 {{ session('admin_user.rol') === 'admin' ? 'bg-purple-100 text-purple-800' : 
                                    (session('admin_user.rol') === 'evaluador_pais' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                        {{ session('admin_user.rol') === 'evaluador_pais' ? 'Evaluador País' : ucfirst(session('admin_user.rol')) }}
                    </span>
                    
                    @if(session('admin_user.rol') === 'evaluador_pais' && session('admin_user.pais_acceso') !== 'ALL')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">
                            📍 {{ session('admin_user.pais_acceso') }}
                        </span>
                    @endif
                </p>
            </div>
            
<!-- Botones de acción -->
<div class="mt-4 md:mt-0 flex space-x-3">
    <button @click="refrescarDatos()" 
            :disabled="cargando"
            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors disabled:opacity-50">
        <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': cargando }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span x-text="cargando ? 'Actualizando...' : 'Actualizar'"></span>
    </button>
</div>
        </div>
    </div>

    <!-- AGREGAR ESTAS LÍNEAS AQUÍ -->
    @include('admin.components.pais-restriction-alert')
    
    <!-- Manejo de errores -->
    @if(isset($error))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error: </strong>
            <span class="block sm:inline">{{ $error }}</span>
        </div>
    @endif

<!-- REEMPLAZAR la sección de "Cards de Estadísticas" en index.blade.php -->
<!-- Desde la línea ~65 hasta ~175 aproximadamente -->

<!-- Cards de Estadísticas - Layout Horizontal Tailwind -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Total Visitas -->
    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
        <div class="p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">
                        Total Visitas
                    </div>
                    <div class="text-lg font-bold text-gray-900">
                        {{ number_format($estadisticas['total_visitas'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Países -->
    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
        <div class="p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-xs font-semibold text-green-600 uppercase tracking-wide mb-1">
                        Países
                    </div>
                    <div class="text-lg font-bold text-gray-900">
                        {{ $estadisticas['total_paises'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tiendas -->
    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
        <div class="p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-xs font-semibold text-yellow-600 uppercase tracking-wide mb-1">
                        Tiendas
                    </div>
                    <div class="text-lg font-bold text-gray-900">
                        {{ $estadisticas['total_tiendas'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluadores -->
    <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-purple-500">
        <div class="p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <div class="text-xs font-semibold text-purple-600 uppercase tracking-wide mb-1">
                        Evaluadores
                    </div>
                    <div class="text-lg font-bold text-gray-900">
                        {{ $estadisticas['total_evaluadores'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Filtros -->
    @include('admin.components.filtros')

    <!-- Contenido Principal -->
    <div class="bg-white shadow rounded-lg">
        <!-- Header de la tabla -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-medium text-gray-900">
                    Visitas Registradas
                    @if($paginacion['total'] > 0)
                        <span class="text-sm font-normal text-gray-500">
                            ({{ number_format($paginacion['total']) }} total{{ $paginacion['total'] != 1 ? 'es' : '' }})
                        </span>
                    @endif
                </h3>
                
                <!-- Selector de elementos por página -->
                <div class="mt-3 sm:mt-0 flex items-center space-x-2">
                    <label class="text-sm text-gray-700">Mostrar:</label>
                    <select onchange="cambiarPaginacion(this.value)" 
                            class="border border-gray-300 rounded px-2 py-1 text-sm">
                        <option value="10" {{ $paginacion['per_page'] == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $paginacion['per_page'] == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $paginacion['per_page'] == 50 ? 'selected' : '' }}>50</option>
                    </select>
                    <span class="text-sm text-gray-700">por página</span>
                </div>
            </div>
        </div>

        <!-- Lista de visitas -->
        @if(count($visitas) > 0)
            <!-- Vista móvil: Cards -->
            <div class="md:hidden">
                <div class="divide-y divide-gray-200">
                    @foreach($visitas as $visita)
                        <div class="p-4">
                            @include('admin.components.tarjeta-visita')
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Vista desktop: Tabla -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tienda / Evaluador
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ubicación
                                @if(session('admin_user.rol') === 'evaluador_pais')
                                    <span class="text-orange-500">*</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Puntuación
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($visitas as $visita)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $visita['TIENDA'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $visita['LIDER_ZONA'] ?? $visita['CORREO_REALIZO'] }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $visita['PAIS'] ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-500">{{ $visita['ZONA'] ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($visita['FECHA_HORA_INICIO'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(isset($visita['puntuacion_general']) && $visita['puntuacion_general'])
                                        @php
                                            $puntuacion = floatval($visita['puntuacion_general']);
                                            $colorClass = $puntuacion >= 0.8 ? 'text-green-500' : ($puntuacion >= 0.6 ? 'text-yellow-500' : 'text-red-500');
                                        @endphp
                                        <div class="flex items-center">
                                            <div class="flex {{ $colorClass }}">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= round($puntuacion * 5))
                                                        ⭐
                                                    @else
                                                        ☆
                                                    @endif
                                                @endfor
                                            </div>
                                            <span class="ml-2 text-sm {{ $colorClass }}">
                                                {{ number_format($puntuacion * 5, 1) }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="{{ route('admin.visita.show', $visita['id']) }}" 
                                           class="text-blue-600 hover:text-blue-900">Ver</a>
                                        <a href="{{ route('admin.visita.imagenes', $visita['id']) }}" 
                                           class="text-gray-600 hover:text-gray-900">📷</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($paginacion['total_pages'] > 1)
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando 
                            <span class="font-medium">{{ (($paginacion['current_page'] - 1) * $paginacion['per_page']) + 1 }}</span>
                            a 
                            <span class="font-medium">{{ min($paginacion['current_page'] * $paginacion['per_page'], $paginacion['total']) }}</span>
                            de 
                            <span class="font-medium">{{ number_format($paginacion['total']) }}</span>
                            resultados
                        </div>
                        
                        <div class="flex space-x-2">
                            @if($paginacion['current_page'] > 1)
                                <a href="?page={{ $paginacion['current_page'] - 1 }}&{{ http_build_query(request()->except('page')) }}" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    Anterior
                                </a>
                            @endif
                            
                            @for($i = max(1, $paginacion['current_page'] - 2); $i <= min($paginacion['total_pages'], $paginacion['current_page'] + 2); $i++)
                                <a href="?page={{ $i }}&{{ http_build_query(request()->except('page')) }}" 
                                   class="px-3 py-2 text-sm font-medium {{ $i == $paginacion['current_page'] ? 'text-blue-600 bg-blue-50 border-blue-500' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' }} border rounded-md">
                                    {{ $i }}
                                </a>
                            @endfor
                            
                            @if($paginacion['current_page'] < $paginacion['total_pages'])
                                <a href="?page={{ $paginacion['current_page'] + 1 }}&{{ http_build_query(request()->except('page')) }}" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    Siguiente
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        @else
            <!-- Estado vacío -->
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay visitas</h3>
                <p class="mt-1 text-sm text-gray-500">
                    No se encontraron visitas con los filtros aplicados.
                </p>
                <div class="mt-6">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Limpiar Filtros
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
function dashboardComponent() {
    return {
        cargando: false,
        
        refrescarDatos() {
            this.cargando = true;
            window.location.reload();
        }
    }
}

function cambiarPaginacion(perPage) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', perPage);
    url.searchParams.delete('page'); // Reset a página 1
    window.location.href = url.toString();
}
</script>
@endsection