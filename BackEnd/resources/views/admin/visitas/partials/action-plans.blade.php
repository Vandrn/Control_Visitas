{{-- Sección de Planes de Acción --}}
<div class="bg-white rounded-lg shadow-sm mb-6" id="planes">
    <div class="border-b border-gray-200 px-6 py-4">
        <h4 class="flex items-center text-lg font-semibold text-gray-800">
            <i class="fas fa-tasks text-gray-600 mr-2"></i> Planes de Acción
        </h4>
    </div>
    
    <div class="p-6">
        @if(isset($visita['planes_accion']) && count($visita['planes_accion']) > 0)
            @php
                $planesConDatos = array_filter($visita['planes_accion'], function($plan) {
                    return !empty($plan['descripcion']);
                });
                
                // Initialize counters
                $totalPlanes = count($planesConDatos);
                $planesVencidos = 0;
                $planesPorVencer = 0;
                $planesEnProgreso = 0;
                $planesPlanificados = 0;
            @endphp
            
            @if(count($planesConDatos) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach($visita['planes_accion'] as $planId => $plan)
                        @if(!empty($plan['descripcion']))
                            @php
                                $fechaVencimiento = !empty($plan['fecha_vencimiento']) ? 
                                    \Carbon\Carbon::parse($plan['fecha_vencimiento']) : null;
                                $hoy = \Carbon\Carbon::now();
                                $diasRestantes = $fechaVencimiento ? $fechaVencimiento->diffInDays($hoy, false) : null;
                                
                                // Calcular estado y clase
                                $estadoClass = 'info'; // Default state
                                $estadoTexto = 'En Progreso';
                                
                                if ($fechaVencimiento) {
                                    if ($diasRestantes < 0) {
                                        $estadoClass = 'danger';
                                        $estadoTexto = 'Vencido';
                                        $planesVencidos++;
                                    } elseif ($diasRestantes <= 3) {
                                        $estadoClass = 'warning';
                                        $estadoTexto = 'Por Vencer';
                                        $planesPorVencer++;
                                    } elseif ($diasRestantes > 3) {
                                        $estadoClass = 'success';
                                        $estadoTexto = 'En Tiempo';
                                        $planesEnProgreso++;
                                    }
                                } else {
                                    $planesPlanificados++;
                                }
                                
                                $numeroPlan = $loop->iteration;
                                
                                // Calcular progreso
                                if ($fechaVencimiento) {
                                    $fechaInicio = \Carbon\Carbon::parse($plan['fecha_inicio'] ?? $visita['fecha_hora_inicio']);
                                    $totalDias = $fechaInicio->diffInDays($fechaVencimiento);
                                    $diasTranscurridos = $fechaInicio->diffInDays($hoy);
                                    $progreso = $totalDias > 0 ? min(100, ($diasTranscurridos / $totalDias) * 100) : 100;
                                }
                            @endphp
                            
                            <div class="bg-white rounded-lg shadow-sm border-l-4 {{ 
                                match($estadoClass) {
                                    'danger' => 'border-red-500',
                                    'warning' => 'border-yellow-500',
                                    'info' => 'border-blue-500',
                                    'success' => 'border-green-500',
                                    default => 'border-gray-500'
                                }
                            }}">
                                <div class="flex justify-between items-center px-4 py-3 border-b border-gray-100">
                                    <h6 class="flex items-center font-medium text-gray-800">
                                        <i class="fas fa-clipboard-check mr-2 {{ 
                                            match($estadoClass) {
                                                'danger' => 'text-red-500',
                                                'warning' => 'text-yellow-500',
                                                'info' => 'text-blue-500',
                                                'success' => 'text-green-500',
                                                default => 'text-gray-500'
                                            }
                                        }}"></i>
                                        Plan {{ $numeroPlan }}
                                    </h6>
                                    <span class="px-3 py-1 text-xs font-medium rounded-full {{ 
                                        match($estadoClass) {
                                            'danger' => 'bg-red-100 text-red-800',
                                            'warning' => 'bg-yellow-100 text-yellow-800',
                                            'info' => 'bg-blue-100 text-blue-800',
                                            'success' => 'bg-green-100 text-green-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        }
                                    }}">
                                        {{ $estadoTexto }}
                                    </span>
                                </div>
                                
                                <div class="p-4">
                                    <p class="text-gray-700">{{ $plan['descripcion'] }}</p>
                                    
                                    @if($fechaVencimiento)
                                        <div class="mt-4 space-y-2">
                                            <div class="flex items-center text-sm text-gray-600">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                <span class="font-medium">Fecha límite:</span> 
                                                <span class="ml-1">{{ $fechaVencimiento->format('d/m/Y') }}</span>
                                            </div>
                                            
                                            <div class="flex items-center text-sm">
                                                <i class="fas fa-clock mr-2"></i>
                                                @if($diasRestantes < 0)
                                                    <span class="text-red-600 font-medium">
                                                        Vencido hace {{ abs($diasRestantes) }} día{{ abs($diasRestantes) != 1 ? 's' : '' }}
                                                    </span>
                                                @elseif($diasRestantes == 0)
                                                    <span class="text-yellow-600 font-medium">
                                                        Vence hoy
                                                    </span>
                                                @else
                                                    <span class="{{ 
                                                        match($estadoClass) {
                                                            'danger' => 'text-red-600',
                                                            'warning' => 'text-yellow-600',
                                                            'info' => 'text-blue-600',
                                                            'success' => 'text-green-600',
                                                            default => 'text-gray-600'
                                                        }
                                                    }} font-medium">
                                                        {{ $diasRestantes }} día{{ $diasRestantes != 1 ? 's' : '' }} restante{{ $diasRestantes != 1 ? 's' : '' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-sm text-gray-600">Progreso temporal</span>
                                                <span class="text-sm text-gray-600">{{ number_format($progreso, 0) }}%</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full {{ 
                                                    match($estadoClass) {
                                                        'danger' => 'bg-red-500',
                                                        'warning' => 'bg-yellow-500',
                                                        'info' => 'bg-blue-500',
                                                        'success' => 'bg-green-500',
                                                        default => 'bg-gray-500'
                                                    }
                                                }}" style="width: {{ $progreso }}%">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
                
                {{-- Resumen de planes --}}
                <div class="mt-8">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h6 class="flex items-center font-semibold text-gray-800 mb-4">
                            <i class="fas fa-chart-pie mr-2"></i> Resumen de Planes de Acción
                        </h6>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-blue-600">
                                    <i class="fas fa-list-alt text-3xl mb-2"></i>
                                    <div class="font-bold text-xl">{{ $totalPlanes }}</div>
                                    <div class="text-sm text-gray-600">Total</div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-red-600">
                                    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
                                    <div class="font-bold text-xl">{{ $planesVencidos }}</div>
                                    <div class="text-sm text-gray-600">Vencidos</div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-yellow-600">
                                    <i class="fas fa-clock text-3xl mb-2"></i>
                                    <div class="font-bold text-xl">{{ $planesPorVencer }}</div>
                                    <div class="text-sm text-gray-600">Por vencer</div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-green-600">
                                    <i class="fas fa-check-circle text-3xl mb-2"></i>
                                    <div class="font-bold text-xl">{{ $planesEnProgreso + $planesPlanificados }}</div>
                                    <div class="text-sm text-gray-600">En tiempo</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-clipboard-list text-4xl text-gray-400 mb-4"></i>
                    <h5 class="text-xl font-medium text-gray-600 mb-2">No hay planes de acción registrados</h5>
                    <p class="text-gray-500">Esta visita no tiene planes de acción definidos.</p>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="fas fa-clipboard-list text-4xl text-gray-400 mb-4"></i>
                <h5 class="text-xl font-medium text-gray-600 mb-2">No hay planes de acción disponibles</h5>
                <p class="text-gray-500">Los datos de planes de acción no están disponibles para esta visita.</p>
            </div>
        @endif
    </div>
</div>