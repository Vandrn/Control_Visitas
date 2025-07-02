{{-- 📍 Componente de Validación de Distancia --}}
@if(isset($validacionDistancia))
    <div class="distance-validation-container mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="card-title mb-2 d-flex align-items-center">
                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                            Validación de Ubicación
                        </h6>
                        
                        <div class="distance-message {{ $validacionDistancia['estado'] }}">
                            {{ $validacionDistancia['mensaje'] }}
                        </div>
                        
                        @if($validacionDistancia['distancia'])
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-ruler me-1"></i>
                                Distancia calculada: {{ $validacionDistancia['distancia'] }} metros
                            </small>
                        @endif
                    </div>
                    
                    <div class="col-md-4 text-md-end">
                        @php
                            $badgeClass = match($validacionDistancia['estado']) {
                                'valida' => 'bg-success',
                                'invalida' => 'bg-danger',
                                'sin_datos' => 'bg-warning',
                                default => 'bg-secondary'
                            };
                            
                            $iconClass = match($validacionDistancia['estado']) {
                                'valida' => 'fa-check-circle',
                                'invalida' => 'fa-exclamation-triangle',
                                'sin_datos' => 'fa-question-circle',
                                default => 'fa-info-circle'
                            };
                        @endphp
                        
                        <span class="badge {{ $badgeClass }} px-3 py-2">
                            <i class="fas {{ $iconClass }} me-1"></i>
                            {{ ucfirst(str_replace('_', ' ', $validacionDistancia['estado'])) }}
                        </span>
                    </div>
                </div>
                
                {{-- Información adicional para debugging (solo para admins) --}}
                @if(session('admin_user.rol') === 'admin' && isset($validacionDistancia['coords_usuario']))
                    <div class="mt-3 pt-3 border-top">
                        <small class="text-muted">
                            <strong>Coordenadas:</strong> 
                            Usuario: {{ number_format($validacionDistancia['coords_usuario']['lat'], 6) }}, {{ number_format($validacionDistancia['coords_usuario']['lng'], 6) }} | 
                            Tienda: {{ number_format($validacionDistancia['coords_tienda']['lat'], 6) }}, {{ number_format($validacionDistancia['coords_tienda']['lng'], 6) }}
                        </small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Estilos específicos --}}
    <style>
        .distance-validation-container .distance-message {
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .distance-message.valida {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .distance-message.invalida {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .distance-message.sin_datos {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .distance-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .distance-validation-container .card-body {
                padding: 1rem !important;
            }
            
            .distance-message {
                font-size: 13px;
                margin-bottom: 10px;
            }
        }
    </style>
@endif