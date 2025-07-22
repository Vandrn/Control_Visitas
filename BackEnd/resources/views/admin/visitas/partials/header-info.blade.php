{{-- Header con información básica de la visita - Versión Mejorada --}}
<div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-purple-600 rounded-lg shadow-lg mb-6">
    {{-- Header Superior --}}
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div class="flex items-center space-x-4">
                <div class="bg-white bg-opacity-20 p-3 rounded-full">
                    <i class="fas fa-store text-xl text-black"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-black">
                        {{ $visita['tienda'] ?? 'Visita sin nombre' }}
                    </h3>
                    <span class="text-indigo-100 flex items-center space-x-2">
                        <i class="fas fa-id-card"></i>
                        <span>ID: {{ $visita['id'] ?? 'N/A' }}</span>
                    </span>
                </div>
            </div>

            {{-- Botones de Acción --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 
                          rounded-lg shadow-sm transition-colors duration-150 ease-in-out">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver al Dashboard
                </a>

                <a href="{{ route('admin.visita.imagenes', $visita['id']) }}"
                    class="inline-flex items-center px-4 py-2 bg-yellow-400 text-sm font-medium text-yellow-900 
                          hover:bg-yellow-500 rounded-lg shadow-sm transition-colors duration-150 ease-in-out">
                    <i class="fas fa-images mr-2"></i>
                    Ver Imágenes
                </a>

            </div>
        </div>
    </div>
</div>

{{-- Cuerpo Principal --}}
<div class="bg-white p-6 rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Información General --}}
        <div class="space-y-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-100 rounded-full">
                    <i class="fas fa-info-circle text-blue-600"></i>
                </div>
                <h5 class="text-lg font-bold text-blue-600">Información General</h5>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-green-100 rounded-full mr-3">
                        <i class="fas fa-globe text-green-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">País</span>
                        <p class="font-semibold text-gray-900">{{ $visita['pais'] ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-yellow-100 rounded-full mr-3">
                        <i class="fas fa-map-marker-alt text-yellow-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Zona</span>
                        <p class="font-semibold text-gray-900">{{ $visita['zona'] ?? 'N/A' }}</p>
                    </div>
                </div>

                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-cyan-100 rounded-full mr-3">
                        <i class="fas fa-store text-cyan-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Tienda</span>
                        <p class="font-semibold text-gray-900">{{ $visita['tienda'] ?? 'N/A' }}</p>
                    </div>
                </div>

            </div>
        </div>

        {{-- Información de Evaluación --}}
        <div class="space-y-4">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-100 rounded-full">
                    <i class="fas fa-user-check text-green-600"></i>
                </div>
                <h5 class="text-lg font-bold text-green-600">Información de Evaluación</h5>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                {{-- Rest of evaluation information with same pattern --}}
                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-blue-100 rounded-full mr-3">
                        <i class="fas fa-calendar text-blue-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Hora de Inicio (UTC-6)</span>
                        <p class="font-semibold text-gray-900">
                            {{ $visita['fecha_hora_inicio_local'] ?? 'N/A' }}
                        </p>
                    </div>
                </div>

                @if($visita['fecha_hora_fin_local'] ?? false)
                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-purple-100 rounded-full mr-3">
                        <i class="fas fa-calendar-check text-purple-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Hora de Finalización (UTC-6)</span>
                        <p class="font-semibold text-gray-900">
                            {{ $visita['fecha_hora_fin_local'] }}
                        </p>
                    </div>
                </div>
                @endif


                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-cyan-100 rounded-full mr-3">
                        <i class="fas fa-user text-cyan-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Evaluador</span>
                        <p class="font-semibold text-gray-900">{{ $visita['lider_zona'] ?? $visita['correo_realizo'] ?? 'N/A' }}</p>
                    </div>
                </div>

                @if($visita['fecha_hora_fin'])
                <div class="flex items-center p-3 bg-white rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-2 bg-green-100 rounded-full mr-3">
                        <i class="fas fa-clock text-green-600"></i>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Duración</span>
                        <p class="font-semibold text-gray-900">
                            @php
                            $inicio = \Carbon\Carbon::parse($visita['fecha_hora_inicio_local']);
                            $fin = \Carbon\Carbon::parse($visita['fecha_hora_fin_local']);
                            $duracion = $inicio->diff($fin);
                            @endphp
                            {{ $duracion->format('%H:%I:%S') }}
                        </p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- CSS Adicional para Animaciones --}}
<style>
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
    }

    .shadow-sm {
        transition: box-shadow 0.3s ease;
    }

    .shadow-sm:hover {
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15) !important;
    }

    .bg-opacity-25 {
        background-color: rgba(255, 255, 255, 0.25) !important;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #dee2e6 !important;
        }
    }
</style>