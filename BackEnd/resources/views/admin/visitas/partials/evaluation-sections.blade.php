{{-- Secciones de evaluación en acordeones --}}
@php
    $secciones = [
        'operaciones' => [
            'titulo' => 'Operaciones',
            'icon' => 'fas fa-cogs',
            'color' => 'primary',
            'preguntas' => $textosPreguntas['operaciones'] ?? []
        ],
        'administracion' => [
            'titulo' => 'Administración', 
            'icon' => 'fas fa-clipboard-list',
            'color' => 'success',
            'preguntas' => $textosPreguntas['administracion'] ?? []
        ],
        'producto' => [
            'titulo' => 'Producto',
            'icon' => 'fas fa-box', 
            'color' => 'warning',
            'preguntas' => $textosPreguntas['producto'] ?? []
        ],
        'personal' => [
            'titulo' => 'Personal',
            'icon' => 'fas fa-users',
            'color' => 'info', 
            'preguntas' => $textosPreguntas['personal'] ?? []
        ],
        'kpis' => [
            'titulo' => 'KPIs',
            'icon' => 'fas fa-chart-line',
            'color' => 'danger',
            'preguntas' => $textosPreguntas['kpis'] ?? []
        ]
    ];
    // Si la visita viene en formato anidado, reorganizar datos
    $seccionesVisita = [];
    if(isset($visita['secciones']) && is_array($visita['secciones'])) {
        foreach($visita['secciones'] as $sec) {
            $seccionesVisita[strtolower($sec['id'])] = $sec;
        }
    }
@endphp

<div class="accordion" id="evaluationAccordion">
    @foreach($secciones as $seccionId => $seccionInfo)
        @php
            $seccionData = $visita[$seccionId] ?? ($seccionesVisita[$seccionId] ?? null);
        @endphp
        @if($seccionData)
            $puntuacion = $puntuaciones[$seccionId] ?? null;
            $isFirst = $loop->first;
        @endphp
            
            <div class="card mb-3" id="{{ $seccionId }}">
                <div class="card-header" id="heading{{ ucfirst($seccionId) }}">
                    <h5 class="mb-0">
                        <button class="btn btn-link w-100 text-left d-flex justify-content-between align-items-center" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse{{ ucfirst($seccionId) }}" 
                                aria-expanded="{{ $isFirst ? 'true' : 'false' }}" 
                                aria-controls="collapse{{ ucfirst($seccionId) }}">
                            
                            <div class="d-flex align-items-center">
                                <i class="{{ $seccionInfo['icon'] }} text-{{ $seccionInfo['color'] }} me-3"></i>
                                <div>
                                    <div class="h6 mb-0">{{ $seccionInfo['titulo'] }}</div>
                                    @if($puntuacion)
                                        <small class="text-muted">
                                            {{ number_format($puntuacion['estrellas'], 1) }}/5.0 
                                            ({{ $puntuacion['porcentaje'] }}% cumplimiento)
                                        </small>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                @if($puntuacion)
                                    <div class="stars me-3">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($puntuacion['estrellas']))
                                                <i class="fas fa-star text-warning"></i>
                                            @elseif($i == ceil($puntuacion['estrellas']) && $puntuacion['estrellas'] > floor($puntuacion['estrellas']))
                                                <i class="fas fa-star-half-alt text-warning"></i>
                                            @else
                                                <i class="far fa-star text-muted"></i>
                                            @endif
                                        @endfor
                                    </div>
                                @endif
                                <i class="fas fa-chevron-down text-muted"></i>
                            </div>
                        </button>
                    </h5>
                </div>

                <div id="collapse{{ ucfirst($seccionId) }}" 
                     class="collapse {{ $isFirst ? 'show' : '' }}" 
                     aria-labelledby="heading{{ ucfirst($seccionId) }}" 
                     data-bs-parent="#evaluationAccordion">
                    
                    <div class="card-body">
                        {{-- Preguntas y respuestas --}}
                        @if(isset($seccionData['preguntas']) && count($seccionData['preguntas']) > 0)
                            <div class="mb-4">
                                <h6 class="text-{{ $seccionInfo['color'] }} mb-3">
                                    <i class="fas fa-question-circle"></i> Evaluación Detallada
                                </h6>
                                
                                @foreach($seccionData['preguntas'] as $preguntaId => $respuesta)
                                    @if($respuesta !== null && isset($seccionInfo['preguntas'][$preguntaId]))
                                        <div class="row mb-3 p-3 bg-light rounded">
                                            <div class="col-md-8">
                                                <p class="mb-2"><strong>{{ $seccionInfo['preguntas'][$preguntaId] }}</strong></p>
                                            </div>
                                            <div class="col-md-4 text-md-end">
                                                @if($seccionId === 'kpis')
                                                    {{-- Para KPIs mostrar Cumple/No Cumple --}}
                                                    @if($respuesta === 'Cumple')
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check"></i> Cumple
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times"></i> No Cumple
                                                        </span>
                                                    @endif
                                                @else
                                                    {{-- Para otras secciones mostrar estrellas --}}
                                                    @php
                                                        $valor = floatval($respuesta);
                                                        $estrellas = round($valor * 5);
                                                        $colorClass = $estrellas >= 4 ? 'text-success' : ($estrellas >= 3 ? 'text-warning' : 'text-danger');
                                                    @endphp
                                                    
                                                    <div class="d-flex align-items-center justify-content-md-end">
                                                        <div class="stars {{ $colorClass }} me-2">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $estrellas)
                                                                    <i class="fas fa-star"></i>
                                                                @else
                                                                    <i class="far fa-star"></i>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                        <span class="badge bg-secondary">
                                                            {{ number_format($valor * 5, 1) }}/5.0
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        
                        {{-- Observaciones --}}
                        @if(!empty($seccionData['observaciones']))
                            <div class="mb-4">
                                <h6 class="text-info mb-3">
                                    <i class="fas fa-comment-alt"></i> Observaciones
                                </h6>
                                <div class="alert alert-info">
                                    <p class="mb-0">{{ $seccionData['observaciones'] }}</p>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Imagen de evidencia --}}
                        @if(!empty($seccionData['imagen_url']))
                            <div class="mb-4">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-camera"></i> Evidencia Fotográfica
                                </h6>
                                <div class="text-center">
                                    <img src="{{ $seccionData['imagen_url'] }}" 
                                         alt="Evidencia {{ $seccionInfo['titulo'] }}"
                                         class="img-fluid rounded shadow-sm"
                                         style="max-height: 300px; cursor: pointer;"
                                         onclick="showImageModal('{{ $seccionData['imagen_url'] }}', '{{ $seccionInfo['titulo'] }}')">
                                    <br>
                                    <small class="text-muted">Click para ampliar</small>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Resumen de la sección --}}
                        @if($puntuacion)
                            <div class="row">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6 class="card-title">Resumen de {{ $seccionInfo['titulo'] }}</h6>
                                            <div class="row">
                                                <div class="col-4">
                                                    <div class="text-{{ $seccionInfo['color'] }}">
                                                        <i class="fas fa-star fa-2x"></i>
                                                        <br>
                                                        <strong>{{ number_format($puntuacion['estrellas'], 1) }}/5.0</strong>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-info">
                                                        <i class="fas fa-percent fa-2x"></i>
                                                        <br>
                                                        <strong>{{ $puntuacion['porcentaje'] }}%</strong>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="text-secondary">
                                                        <i class="fas fa-list fa-2x"></i>
                                                        <br>
                                                        <strong>{{ $puntuacion['total_preguntas'] }}</strong> preguntas
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>