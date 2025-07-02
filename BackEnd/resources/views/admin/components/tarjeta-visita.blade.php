<div class="bg-white shadow rounded-lg hover:shadow-md transition-shadow duration-200 border border-gray-200">
    <div class="p-6">
        <!-- Header de la tarjeta -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <h3 class="text-lg font-medium text-gray-900 mb-1">
                    {{ $visita['TIENDA'] ?? 'Tienda no especificada' }}
                </h3>
                <p class="text-sm text-gray-600">
                    {{ $visita['PAIS'] ?? '' }}{{ !empty($visita['ZONA']) ? ' • ' . $visita['ZONA'] : '' }}
                </p>
            </div>
            
            <!-- Puntuación general -->
            @if(isset($visita['puntuacion_general']) && $visita['puntuacion_general'])
                <div class="flex items-center space-x-1">
                    @php
                        $puntuacion = floatval($visita['puntuacion_general']);
                        $estrellas = round($puntuacion * 5); // Convertir de 0-1 a 0-5
                        $colorClass = $puntuacion >= 0.8 ? 'text-green-500' : ($puntuacion >= 0.6 ? 'text-yellow-500' : 'text-red-500');
                    @endphp
                    
                    <div class="flex {{ $colorClass }}">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= $estrellas)
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-gray-300 fill-current" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            @endif
                        @endfor
                    </div>
                    <span class="text-sm font-medium {{ $colorClass }}">
                        {{ number_format($puntuacion * 5, 1) }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Información de la visita -->
        <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-5 8l2 2 4-4M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ \Carbon\Carbon::parse($visita['FECHA_HORA_INICIO'])->format('d/m/Y H:i') }}
            </div>

            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ $visita['LIDER_ZONA'] ?? $visita['CORREO_REALIZO'] }}
            </div>

            @if(!empty($visita['UBICACION']))
                <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ $visita['UBICACION'] }}
                </div>
            @endif
        </div>

        <!-- Puntuación por áreas -->
        @if(isset($visita['puntuacion_operaciones']) && $visita['puntuacion_operaciones'])
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-gray-600">Operaciones</span>
                    <span class="font-medium">{{ number_format($visita['puntuacion_operaciones'] * 5, 1) }}/5.0</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $visita['puntuacion_operaciones'] * 100 }}%"></div>
                </div>
            </div>
        @endif

        <!-- Botones de acción -->
        <div class="flex space-x-3 pt-4 border-t border-gray-200">
            <a href="{{ route('admin.visita.show', $visita['id']) }}" 
               class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Ver Detalle
            </a>
            
            <a href="{{ route('admin.visita.imagenes', $visita['id']) }}" 
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                📷 Imágenes
            </a>
        </div>
    </div>
</div>