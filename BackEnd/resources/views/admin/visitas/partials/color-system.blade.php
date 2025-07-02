{{-- 
    ARCHIVO: resources/views/admin/visitas/partials/color-system.blade.php
    SISTEMA DE COLORES AVANZADO CON BADGES DINÁMICOS E INDICADORES
--}}

@php
/**
 * Genera badge dinámico basado en valor y contexto
 * @param float $value Valor a evaluar
 * @param string $context Contexto: 'rating', 'kpi', 'plan', 'trend'
 * @param array $thresholds Umbrales personalizados (opcional)
 * @return array ['class' => 'badge-success', 'text' => 'Excelente', 'icon' => 'fa-check']
 */
function getDynamicBadge($value, $context = 'rating', $thresholds = null) {
    $badge = ['class' => '', 'text' => '', 'icon' => ''];
    
    switch ($context) {
        case 'rating':
            if ($value >= 4.5) {
                $badge = ['class' => 'badge-success', 'text' => 'Excelente', 'icon' => 'fa-star'];
            } elseif ($value >= 4.0) {
                $badge = ['class' => 'badge-success', 'text' => 'Muy Bueno', 'icon' => 'fa-thumbs-up'];
            } elseif ($value >= 3.5) {
                $badge = ['class' => 'badge-info', 'text' => 'Bueno', 'icon' => 'fa-check'];
            } elseif ($value >= 3.0) {
                $badge = ['class' => 'badge-warning', 'text' => 'Regular', 'icon' => 'fa-minus'];
            } elseif ($value >= 2.0) {
                $badge = ['class' => 'badge-danger', 'text' => 'Bajo', 'icon' => 'fa-exclamation-triangle'];
            } else {
                $badge = ['class' => 'badge-danger', 'text' => 'Muy Bajo', 'icon' => 'fa-times'];
            }
            break;
            
        case 'kpi':
            if ($value >= 80) {
                $badge = ['class' => 'badge-success', 'text' => 'Cumple', 'icon' => 'fa-check-circle'];
            } elseif ($value >= 60) {
                $badge = ['class' => 'badge-warning', 'text' => 'Parcial', 'icon' => 'fa-clock'];
            } else {
                $badge = ['class' => 'badge-danger', 'text' => 'No Cumple', 'icon' => 'fa-times-circle'];
            }
            break;
            
        case 'plan':
            if ($value > 7) {
                $badge = ['class' => 'badge-success', 'text' => 'A Tiempo', 'icon' => 'fa-calendar-check'];
            } elseif ($value > 0) {
                $badge = ['class' => 'badge-warning', 'text' => 'Próximo', 'icon' => 'fa-calendar-alt'];
            } elseif ($value == 0) {
                $badge = ['class' => 'badge-warning', 'text' => 'Hoy', 'icon' => 'fa-exclamation-triangle'];
            } else {
                $badge = ['class' => 'badge-danger', 'text' => 'Vencido', 'icon' => 'fa-calendar-times'];
            }
            break;
            
        case 'trend':
            if ($value > 5) {
                $badge = ['class' => 'badge-success', 'text' => 'Mejorando', 'icon' => 'fa-arrow-up'];
            } elseif ($value > -5) {
                $badge = ['class' => 'badge-info', 'text' => 'Estable', 'icon' => 'fa-arrows-alt-h'];
            } else {
                $badge = ['class' => 'badge-danger', 'text' => 'Deteriorando', 'icon' => 'fa-arrow-down'];
            }
            break;
            
        default:
            $badge = ['class' => 'badge-secondary', 'text' => 'N/A', 'icon' => 'fa-question'];
    }
    
    return $badge;
}

/**
 * Genera indicador de rendimiento por sección
 * @param array $scores Array de puntuaciones de la sección
 * @param string $sectionName Nombre de la sección
 * @return string HTML del indicador de sección
 */
function generateSectionIndicator($scores, $sectionName) {
    $average = array_sum($scores) / count($scores);
    $badge = getDynamicBadge($average, 'rating');
    
    // Calcular distribución de puntuaciones
    $distribution = [
        'excellent' => 0, // 4.5-5.0
        'good' => 0,      // 3.5-4.4
        'regular' => 0,   // 2.5-3.4
        'poor' => 0       // 0-2.4
    ];
    
    foreach ($scores as $score) {
        if ($score >= 4.5) $distribution['excellent']++;
        elseif ($score >= 3.5) $distribution['good']++;
        elseif ($score >= 2.5) $distribution['regular']++;
        else $distribution['poor']++;
    }
    
    $html = '<div class="section-indicator">';
    $html .= '<div class="section-header">';
    $html .= '<h6 class="section-name">' . $sectionName . '</h6>';
    $html .= '<span class="badge ' . $badge['class'] . '">';
    $html .= '<i class="fas ' . $badge['icon'] . '"></i> ' . $badge['text'];
    $html .= '</span>';
    $html .= '</div>';
    
    // Barra de distribución
    $total = count($scores);
    $html .= '<div class="distribution-bar">';
    $html .= '<div class="bar-segment excellent" style="width: ' . ($distribution['excellent']/$total*100) . '%"></div>';
    $html .= '<div class="bar-segment good" style="width: ' . ($distribution['good']/$total*100) . '%"></div>';
    $html .= '<div class="bar-segment regular" style="width: ' . ($distribution['regular']/$total*100) . '%"></div>';
    $html .= '<div class="bar-segment poor" style="width: ' . ($distribution['poor']/$total*100) . '%"></div>';
    $html .= '</div>';
    
    // Leyenda
    $html .= '<div class="distribution-legend">';
    $html .= '<span class="legend-item excellent">' . $distribution['excellent'] . ' Excelente</span>';
    $html .= '<span class="legend-item good">' . $distribution['good'] . ' Bueno</span>';
    $html .= '<span class="legend-item regular">' . $distribution['regular'] . ' Regular</span>'; 
    $html .= '<span class="legend-item poor">' . $distribution['poor'] . ' Bajo</span>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera alerta contextual basada en múltiples factores
 * @param array $data Datos de la visita
 * @return string HTML de alertas
 */
function generateContextualAlerts($data) {
    $alerts = [];
    
    // Analizar promedio general
    $generalAvg = $data['promedio_general'] ?? 0;
    if ($generalAvg < 3.0) {
        $alerts[] = [
            'type' => 'danger',
            'icon' => 'fa-exclamation-triangle',
            'title' => 'Atención Requerida',
            'message' => 'La evaluación general está por debajo del estándar mínimo (3.0/5.0)'
        ];
    }
    
    // Analizar planes vencidos
    $planesVencidos = $data['planes_vencidos'] ?? 0;
    if ($planesVencidos > 0) {
        $alerts[] = [
            'type' => 'warning',
            'icon' => 'fa-calendar-times',
            'title' => 'Planes Vencidos',
            'message' => $planesVencidos . ' plan(es) de acción han vencido sin completarse'
        ];
    }
    
    // Analizar KPIs críticos
    $kpisCriticos = $data['kpis_no_cumple'] ?? 0;
    if ($kpisCriticos >= 3) {
        $alerts[] = [
            'type' => 'danger',
            'icon' => 'fa-chart-line',
            'title' => 'KPIs Críticos',
            'message' => $kpisCriticos . ' KPIs no están cumpliendo con los objetivos'
        ];
    }
    
    // Analizar mejoras destacadas
    $mejoraSignificativa = $data['mejora_vs_anterior'] ?? 0;
    if ($mejoraSignificativa > 0.5) {
        $alerts[] = [
            'type' => 'success',
            'icon' => 'fa-trophy',
            'title' => 'Mejora Destacada',
            'message' => 'Excelente progreso comparado con la evaluación anterior (+' . number_format($mejoraSignificativa, 1) . ')'
        ];
    }
    
    if (empty($alerts)) {
        return '';
    }
    
    $html = '<div class="contextual-alerts">';
    foreach ($alerts as $alert) {
        $html .= '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show">';
        $html .= '<i class="fas ' . $alert['icon'] . '"></i>';
        $html .= '<strong>' . $alert['title'] . ':</strong> ' . $alert['message'];
        $html .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
        $html .= '</div>';
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera mapa de calor para identificar áreas problemáticas
 * @param array $sectionScores Puntuaciones por sección
 * @return string HTML del mapa de calor
 */
function generateHeatMap($sectionScores) {
    $html = '<div class="performance-heatmap">';
    $html .= '<h6 class="heatmap-title">Mapa de Rendimiento</h6>';
    $html .= '<div class="heatmap-grid">';
    
    foreach ($sectionScores as $section => $score) {
        $intensity = $score / 5; // Normalizar a 0-1
        $hue = $intensity * 120; // Verde (120) a Rojo (0)
        
        $html .= '<div class="heatmap-cell" style="background-color: hsl(' . $hue . ', 70%, 85%)">';
        $html .= '<div class="cell-label">' . $section . '</div>';
        $html .= '<div class="cell-score">' . number_format($score, 1) . '</div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    // Escala de colores
    $html .= '<div class="heatmap-scale">';
    $html .= '<span class="scale-label">Bajo</span>';
    $html .= '<div class="scale-gradient"></div>';
    $html .= '<span class="scale-label">Alto</span>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}
@endphp

{{-- CSS para el sistema de colores avanzado --}}
<style>
/* Badges Dinámicos Mejorados */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    border-radius: 6px;
    font-size: 0.875rem;
    padding: 6px 12px;
    transition: all 0.3s ease;
}

.badge i {
    font-size: 0.8em;
}

.badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Colores de badges */
.badge-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
}

.badge-info {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
    color: white;
    box-shadow: 0 2px 4px rgba(23, 162, 184, 0.3);
}

.badge-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
    box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
}

.badge-danger {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
    color: white;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.badge-secondary {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
    box-shadow: 0 2px 4px rgba(108, 117, 125, 0.3);
}

/* Indicadores de Sección */
.section-indicator {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 15px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.section-name {
    margin: 0;
    font-weight: 600;
    color: #495057;
}

/* Barra de Distribución */
.distribution-bar {
    display: flex;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
}

.bar-segment {
    transition: all 0.3s ease;
}

.bar-segment.excellent {
    background: linear-gradient(90deg, #28a745, #20c997);
}

.bar-segment.good {
    background: linear-gradient(90deg, #17a2b8, #6f42c1);
}

.bar-segment.regular {
    background: linear-gradient(90deg, #ffc107, #fd7e14);
}

.bar-segment.poor {
    background: linear-gradient(90deg, #dc3545, #e83e8c);
}

/* Leyenda de Distribución */
.distribution-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    font-size: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.legend-item::before {
    content: '';
    width: 12px;
    height: 12px;
    border-radius: 2px;
    display: inline-block;
}

.legend-item.excellent::before {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.legend-item.good::before {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
}

.legend-item.regular::before {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.legend-item.poor::before {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
}

/* Alertas Contextuales */
.contextual-alerts {
    margin-bottom: 20px;
}

.alert {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.alert-success {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
    border-left-color: #28a745;
    color: #155724;
}

.alert-info {
    background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(111, 66, 193, 0.1));
    border-left-color: #17a2b8;
    color: #0c5460;
}

.alert-warning {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(253, 126, 20, 0.1));
    border-left-color: #ffc107;
    color: #856404;
}

.alert-danger {
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(232, 62, 140, 0.1));
    border-left-color: #dc3545;
    color: #721c24;
}

.alert i {
    margin-right: 8px;
    font-size: 1.1em;
}

/* Mapa de Calor */
.performance-heatmap {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.heatmap-title {
    text-align: center;
    margin-bottom: 20px;
    color: #495057;
    font-weight: 600;
}

.heatmap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}

.heatmap-cell {
    aspect-ratio: 1;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 2px solid transparent;
}

.heatmap-cell:hover {
    transform: scale(1.05);
    border-color: rgba(0,0,0,0.2);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.cell-label {
    font-size: 12px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.cell-score {
    font-size: 18px;
    font-weight: bold;
    color: #212529;
}

/* Escala de Colores */
.heatmap-scale {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
}

.scale-gradient {
    width: 200px;
    height: 20px;
    background: linear-gradient(90deg, 
        hsl(0, 70%, 85%) 0%,
        hsl(30, 70%, 85%) 25%,
        hsl(60, 70%, 85%) 50%,
        hsl(90, 70%, 85%) 75%,
        hsl(120, 70%, 85%) 100%
    );
    border-radius: 10px;
    border: 1px solid #dee2e6;
}

.scale-label {
    font-size: 12px;
    font-weight: 500;
    color: #6c757d;
}

/* Indicadores de Estado Mejorados */
.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-indicator.status-excellent {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-indicator.status-good {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.3);
}

.status-indicator.status-regular {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.status-indicator.status-poor {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .distribution-legend {
        justify-content: center;
    }
    
    .heatmap-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .scale-gradient {
        width: 150px;
    }
}

@media (max-width: 576px) {
    .contextual-alerts .alert {
        font-size: 14px;
        padding: 12px;
    }
    
    .heatmap-grid {
        grid-template-columns: 1fr;
    }
    
    .performance-heatmap {
        padding: 15px;
    }
    
    .cell-score {
        font-size: 16px;
    }
}

/* Animaciones */
.badge {
    animation: badgeSlideIn 0.5s ease-out;
}

@keyframes badgeSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.heatmap-cell {
    animation: cellFadeIn 0.6s ease-out;
}

@keyframes cellFadeIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.alert {
    animation: alertSlideDown 0.4s ease-out;
}

@keyframes alertSlideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estados de hover y focus mejorados */
.badge:focus,
.heatmap-cell:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Efectos de pulso para elementos críticos */
.badge-danger,
.alert-danger {
    animation: criticalPulse 2s infinite;
}

@keyframes criticalPulse {
    0%, 100% {
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    }
    50% {
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.5);
    }
}
</style>

{{-- JavaScript para funcionalidades del sistema de colores --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips para elementos del mapa de calor
    $('.heatmap-cell').tooltip({
        title: function() {
            const label = $(this).find('.cell-label').text();
            const score = $(this).find('.cell-score').text();
            return `${label}: ${score}/5.0`;
        },
        placement: 'top'
    });
    
    // Animar barras de distribución
    const distributionBars = document.querySelectorAll('.distribution-bar');
    
    if ('IntersectionObserver' in window) {
        const barObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const segments = entry.target.querySelectorAll('.bar-segment');
                    segments.forEach((segment, index) => {
                        const width = segment.style.width;
                        segment.style.width = '0%';
                        segment.style.transition = `width 0.8s ease ${index * 0.1}s`;
                        
                        setTimeout(() => {
                            segment.style.width = width;
                        }, 100);
                    });
                    
                    barObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        distributionBars.forEach(bar => {
            barObserver.observe(bar);
        });
    }
    
    // Auto-dismiss para alertas no críticas
    setTimeout(() => {
        $('.alert-info, .alert-success').fadeOut(500);
    }, 8000);
    
    // Efecto de hover mejorado para badges
    document.querySelectorAll('.badge').forEach(badge => {
        badge.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.05)';
        });
        
        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Click handler para celdas del mapa de calor
    document.querySelectorAll('.heatmap-cell').forEach(cell => {
        cell.addEventListener('click', function() {
            const label = this.querySelector('.cell-label').textContent;
            const score = this.querySelector('.cell-score').textContent;
            
            // Mostrar modal o expandir información (implementar según necesidad)
            console.log(`Sección seleccionada: ${label} (${score}/5.0)`);
        });
    });
});
</script>