{{-- Resumen de puntuaciones por sección --}}
<div class="card mb-4">
    <div class="card-header">
        <h4 class="mb-0">
            <i class="fas fa-chart-bar"></i> Resumen de Evaluación
        </h4>
    </div>
    <div class="card-body">
        <div class="row">
            @php
                $secciones = [
                    'operaciones' => ['titulo' => 'Operaciones', 'icon' => 'fas fa-cogs', 'color' => 'primary'],
                    'administracion' => ['titulo' => 'Administración', 'icon' => 'fas fa-clipboard-list', 'color' => 'success'],
                    'producto' => ['titulo' => 'Producto', 'icon' => 'fas fa-box', 'color' => 'warning'],
                    'personal' => ['titulo' => 'Personal', 'icon' => 'fas fa-users', 'color' => 'info'],
                    'kpis' => ['titulo' => 'KPIs', 'icon' => 'fas fa-chart-line', 'color' => 'danger']
                ];
            @endphp
            
            @foreach($secciones as $seccionId => $seccionInfo)
                @if(isset($puntuaciones[$seccionId]))
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card h-100 border-{{ $seccionInfo['color'] }}">
                            <div class="card-body text-center">
                                <i class="{{ $seccionInfo['icon'] }} fa-2x text-{{ $seccionInfo['color'] }} mb-2"></i>
                                <h6 class="card-title">{{ $seccionInfo['titulo'] }}</h6>
                                
                                {{-- Puntuación --}}
                                <div class="mb-2">
                                    @php
                                        $puntuacion = $puntuaciones[$seccionId];
                                        $estrellas = $puntuacion['estrellas'];
                                        $colorClass = '';
                                        if ($estrellas >= 4.5) $colorClass = 'text-success';
                                        elseif ($estrellas >= 3.5) $colorClass = 'text-warning';
                                        else $colorClass = 'text-danger';
                                    @endphp
                                    
                                    <div class="stars {{ $colorClass }}">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($estrellas))
                                                <i class="fas fa-star"></i>
                                            @elseif($i == ceil($estrellas) && $estrellas > floor($estrellas))
                                                <i class="fas fa-star-half-alt"></i>
                                            @else
                                                <i class="far fa-star"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    
                                    <div class="mt-1">
                                        <strong class="{{ $colorClass }}">{{ number_format($estrellas, 1) }}/5.0</strong>
                                    </div>
                                </div>
                                
                                {{-- Barra de progreso --}}
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $seccionInfo['color'] }}" 
                                         role="progressbar" 
                                         style="width: {{ $puntuacion['porcentaje'] }}%"
                                         aria-valuenow="{{ $puntuacion['porcentaje'] }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                
                                <small class="text-muted">
                                    {{ $puntuacion['total_preguntas'] }} pregunta{{ $puntuacion['total_preguntas'] != 1 ? 's' : '' }}
                                </small>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        
        {{-- Puntuación general grande --}}
        @if(isset($puntuaciones['general']))
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title">
                            <i class="fas fa-trophy text-warning"></i> Puntuación General
                        </h5>
                        @php
                            $general = $puntuaciones['general'];
                            $colorClass = '';
                            if ($general['estrellas'] >= 4.5) $colorClass = 'text-success';
                            elseif ($general['estrellas'] >= 3.5) $colorClass = 'text-warning';
                            else $colorClass = 'text-danger';
                        @endphp
                        
                        <div class="mb-3">
                            <span class="display-4 {{ $colorClass }}">
                                {{ number_format($general['estrellas'], 1) }}
                            </span>
                            <span class="text-muted">/5.0</span>
                        </div>
                        
                        <div class="stars {{ $colorClass }} mb-3" style="font-size: 1.5rem;">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($general['estrellas']))
                                    <i class="fas fa-star"></i>
                                @elseif($i == ceil($general['estrellas']) && $general['estrellas'] > floor($general['estrellas']))
                                    <i class="fas fa-star-half-alt"></i>
                                @else
                                    <i class="far fa-star"></i>
                                @endif
                            @endfor
                        </div>
                        
                        <div class="progress mx-auto" style="width: 300px; height: 15px;">
                            <div class="progress-bar {{ $colorClass == 'text-success' ? 'bg-success' : ($colorClass == 'text-warning' ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" 
                                 style="width: {{ $general['porcentaje'] }}%"
                                 aria-valuenow="{{ $general['porcentaje'] }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        
                        <p class="mt-3 mb-0 text-muted">
                            {{ $general['porcentaje'] }}% de cumplimiento general
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>