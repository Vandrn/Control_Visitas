@php
function calculateKpiScore($respuestas) {
    if (empty($respuestas)) {
        \Log::debug('KPI respuestas empty');
        return 0;
    }
    
    $total = 0;
    $cumple = 0;
    
    foreach ($respuestas as $key => $resp) {
        if ($resp !== null && $resp !== '') {
            $total++;
            $normalizedResp = strtolower(trim($resp));
            \Log::debug("KPI $key: '$normalizedResp'");
            
            if ($normalizedResp === 'cumple' || $normalizedResp === '1' || $resp === 1) {
                $cumple++;
            }
        }
    }
    
    $score = $total > 0 ? ($cumple / $total) : 0;
    \Log::debug("KPI Score - Total: $total, Cumple: $cumple, Score: $score");
    return $score;
}

function generateStars($puntuacion, $size = 'md', $showText = true, $isKpi = false) {
    $puntuacion = floatval($puntuacion);
    $estrellas = $isKpi ? ($puntuacion * 5) : round($puntuacion * 5, 1);
    $estrellasCompletas = floor($estrellas);
    $tieneMedia = ($estrellas - $estrellasCompletas) >= 0.5;
    
    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5', 
        'lg' => 'w-6 h-6',
        'xl' => 'w-8 h-8'
    ];
    
    $colorClass = $estrellas >= 4 ? 'text-green-500' : 
                 ($estrellas >= 3 ? 'text-yellow-500' : 
                 ($estrellas >= 2 ? 'text-orange-500' : 'text-red-500'));
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    
    $html = '<div class="flex items-center space-x-1">';
    $html .= '<div class="flex ' . $colorClass . '">';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $estrellasCompletas) {
            $html .= '<svg class="' . $sizeClass . ' fill-current" viewBox="0 0 20 20">';
            $html .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>';
            $html .= '</svg>';
        } elseif ($i == $estrellasCompletas + 1 && $tieneMedia) {
            $html .= '<div class="relative ' . $sizeClass . '">';
            $html .= '<svg class="' . $sizeClass . ' text-gray-300 fill-current absolute" viewBox="0 0 20 20">';
            $html .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>';
            $html .= '</svg>';
            $html .= '<svg class="' . $sizeClass . ' ' . $colorClass . ' fill-current relative overflow-hidden" viewBox="0 0 20 20" style="clip-path: inset(0 50% 0 0);">';
            $html .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>';
            $html .= '</svg>';
            $html .= '</div>';
        } else {
            $html .= '<svg class="' . $sizeClass . ' text-gray-300 fill-current" viewBox="0 0 20 20">';
            $html .= '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>';
            $html .= '</svg>';
        }
    }
    
    $html .= '</div>';
    
    if ($showText) {
        $html .= '<span class="text-sm font-medium ' . $colorClass . ' ml-2">';
        if ($isKpi) {
            $html .= number_format($puntuacion * 100, 0) . '% cumplimiento';
        } else {
            $html .= number_format($estrellas, 1) . '/5.0';
            $html .= '<span class="text-xs text-gray-500 ml-1">(' . number_format($puntuacion * 100, 1) . '%)</span>';
        }
        $html .= '</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}

function generateProgressBar($puntuacion, $height = 'h-2', $showPercentage = true) {
    $puntuacion = floatval($puntuacion);
    $porcentaje = $puntuacion * 100;
    
    $colorClass = $porcentaje >= 80 ? 'bg-green-500' : 
                 ($porcentaje >= 60 ? 'bg-yellow-500' : 
                 ($porcentaje >= 40 ? 'bg-orange-500' : 'bg-red-500'));
    
    $html = '<div class="w-full">';
    
    if ($showPercentage) {
        $html .= '<div class="flex justify-between items-center mb-1">';
        $html .= '<span class="text-sm font-medium text-gray-700">Progreso</span>';
        $html .= '<span class="text-sm font-medium text-gray-900">' . number_format($porcentaje, 1) . '%</span>';
        $html .= '</div>';
    }
    
    $html .= '<div class="w-full bg-gray-200 rounded-full ' . $height . '">';
    $html .= '<div class="' . $colorClass . ' ' . $height . ' rounded-full transition-all duration-500 ease-out" style="width: ' . $porcentaje . '%"></div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function generateCircularProgress($puntuacion, $size = 60, $strokeWidth = 4) {
    $puntuacion = floatval($puntuacion);
    $porcentaje = $puntuacion * 100;
    $radius = ($size - $strokeWidth) / 2;
    $circumference = 2 * pi() * $radius;
    $strokeDasharray = $circumference;
    $strokeDashoffset = $circumference - ($porcentaje / 100) * $circumference;
    
    $colorClass = $porcentaje >= 80 ? '#10b981' : 
                 ($porcentaje >= 60 ? '#f59e0b' : 
                 ($porcentaje >= 40 ? '#f97316' : '#ef4444'));
    
    $html = '<div class="relative inline-flex items-center justify-center">';
    $html .= '<svg class="transform -rotate-90" width="' . $size . '" height="' . $size . '">';
    $html .= '<circle cx="' . ($size/2) . '" cy="' . ($size/2) . '" r="' . $radius . '" stroke="#e5e7eb" stroke-width="' . $strokeWidth . '" fill="none"/>';
    $html .= '<circle cx="' . ($size/2) . '" cy="' . ($size/2) . '" r="' . $radius . '" stroke="' . $colorClass . '" stroke-width="' . $strokeWidth . '" fill="none" stroke-linecap="round" stroke-dasharray="' . $strokeDasharray . '" stroke-dashoffset="' . $strokeDashoffset . '" class="transition-all duration-500 ease-out"/>';
    $html .= '</svg>';
    $html .= '<div class="absolute inset-0 flex items-center justify-center">';
    $html .= '<span class="text-xs font-semibold" style="color: ' . $colorClass . '">' . number_format($porcentaje, 0) . '%</span>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

function generateKpiIcon($cumplimiento) {
    $porcentaje = $cumplimiento * 100;
    if ($porcentaje >= 80) {
        return '<i class="fas fa-check-circle text-green-600"></i>';
    } elseif ($porcentaje >= 60) {
        return '<i class="fas fa-exclamation-triangle text-yellow-600"></i>';
    } else {
        return '<i class="fas fa-times-circle text-red-600"></i>';
    }
}
@endphp

{{-- Sección de Puntuación Visual --}}
<div class="bg-white shadow-sm rounded-lg mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Puntuación por Áreas
        </h3>
    </div>
    
    <div class="p-6">
        {{-- Grid de Puntuaciones por Sección --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach(['operaciones', 'administracion', 'producto', 'personal', 'kpis'] as $seccion)
                @if(isset($puntuaciones[$seccion]))
                    @php
                        $puntuacion = $puntuaciones[$seccion];
                        
                        // Special handling for KPIs
                        if ($seccion === 'kpis') {
                            \Log::debug('Processing KPIs section');
                            \Log::debug('Raw KPI data: ' . json_encode($puntuacion));
                            
                            // Get KPI responses from the visit data
                            $kpiResponses = [];
                            if (isset($visita['kpis']['preguntas'])) {
                                $kpiResponses = $visita['kpis']['preguntas'];
                            }
                            
                            $kpiScore = calculateKpiScore($kpiResponses);
                            \Log::debug('Final KPI score: ' . $kpiScore);
                            
                            $puntuacion['promedio'] = $kpiScore;
                            $puntuacion['porcentaje'] = $kpiScore * 100;
                            $puntuacion['estrellas'] = $kpiScore * 5;
                            $puntuacion['total_preguntas'] = count(array_filter($kpiResponses, function($resp) {
                                return $resp !== null && $resp !== '';
                            }));
                        }
                        
                        $titulo = ucfirst($seccion);
                        if($seccion === 'administracion') $titulo = 'Administración';
                        if($seccion === 'kpis') $titulo = 'KPIs';
                        
                        $iconClass = match($seccion) {
                            'operaciones' => 'fa-cogs',
                            'administracion' => 'fa-file-alt',
                            'producto' => 'fa-box',
                            'personal' => 'fa-users',
                            'kpis' => 'fa-chart-bar',
                            default => 'fa-chart-line'
                        };
                    @endphp
                    
                    <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    @if($seccion === 'kpis')
                                        {!! generateKpiIcon($puntuacion['promedio']) !!}
                                    @else
                                        <i class="fas {{ $iconClass }} text-blue-600"></i>
                                    @endif
                                </div>
                                <div>
                                    <h5 class="font-semibold text-gray-900">{{ $titulo }}</h5>
                                    <p class="text-xs text-gray-500">
                                        {{ $puntuacion['total_preguntas'] ?? 0 }} 
                                        @if($seccion === 'kpis')
                                            indicador{{ ($puntuacion['total_preguntas'] ?? 0) != 1 ? 'es' : '' }}
                                        @else
                                            pregunta{{ ($puntuacion['total_preguntas'] ?? 0) != 1 ? 's' : '' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                {!! generateCircularProgress($puntuacion['promedio'], 50, 3) !!}
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                {!! generateStars($puntuacion['promedio'], 'sm', true, $seccion === 'kpis') !!}
                            </div>
                            
                            <div>
                                {!! generateProgressBar($puntuacion['promedio'], 'h-2', false) !!}
                            </div>
                            
                            <div class="flex justify-between items-center">
                                @php
                                    $porcentaje = $puntuacion['porcentaje'];
                                    $badge = $porcentaje >= 80 ? ['text' => 'Excelente', 'class' => 'bg-green-100 text-green-800'] : 
                                            ($porcentaje >= 60 ? ['text' => 'Bueno', 'class' => 'bg-yellow-100 text-yellow-800'] : 
                                            ($porcentaje >= 40 ? ['text' => 'Regular', 'class' => 'bg-orange-100 text-orange-800'] : 
                                            ['text' => 'Deficiente', 'class' => 'bg-red-100 text-red-800']));
                                @endphp
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                                    {{ $badge['text'] }}
                                </span>
                                
                                <span class="text-sm font-semibold text-gray-700">
                                    @if($seccion === 'kpis')
                                        {{ number_format($puntuacion['porcentaje'], 0) }}% cumple
                                    @else
                                        {{ number_format($puntuacion['estrellas'], 1) }}/5.0
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>