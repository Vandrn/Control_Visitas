{{-- 
    ARCHIVO: resources/views/admin/visitas/partials/enhanced-stars.blade.php
    SISTEMA DE ESTRELLAS MEJORADO CON TOOLTIPS Y ANIMACIONES
--}}

@php
/**
 * Genera sistema de estrellas mejorado con tooltips y animaciones
 * @param float $rating Calificación de 0 a 5
 * @param string $label Etiqueta descriptiva opcional
 * @param bool $showTooltip Mostrar tooltip informativo
 * @param bool $animated Aplicar animaciones de entrada
 * @param string $size Tamaño: 'sm', 'md', 'lg'
 * @return string HTML del sistema de estrellas
 */
function generateEnhancedStars($rating, $label = '', $showTooltip = true, $animated = true, $size = 'md') {
    $rating = max(0, min(5, $rating));
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
    
    // Determinar clase de color basada en rating
    $colorClass = '';
    $performanceLabel = '';
    if ($rating >= 4.5) {
        $colorClass = 'text-success';
        $performanceLabel = 'Excelente';
    } elseif ($rating >= 4.0) {
        $colorClass = 'text-success';
        $performanceLabel = 'Muy Bueno';
    } elseif ($rating >= 3.5) {
        $colorClass = 'text-warning';
        $performanceLabel = 'Bueno';
    } elseif ($rating >= 3.0) {
        $colorClass = 'text-warning';
        $performanceLabel = 'Regular';
    } elseif ($rating >= 2.0) {
        $colorClass = 'text-danger';
        $performanceLabel = 'Bajo';
    } else {
        $colorClass = 'text-danger';
        $performanceLabel = 'Muy Bajo';
    }
    
    // Clases de tamaño
    $sizeClasses = [
        'sm' => 'star-sm',
        'md' => 'star-md', 
        'lg' => 'star-lg'
    ];
    $starSizeClass = $sizeClasses[$size] ?? 'star-md';
    
    // ID único para tooltips
    $uniqueId = 'stars-' . uniqid();
    
    $html = '<div class="enhanced-stars-container ' . ($animated ? 'stars-animated' : '') . '">';
    
    // Contenedor de estrellas
    $html .= '<div class="stars-wrapper ' . $starSizeClass . '" ' . 
             ($showTooltip ? 'data-toggle="tooltip" data-placement="top" title="' . $performanceLabel . ': ' . number_format($rating, 1) . '/5.0"' : '') . 
             ' id="' . $uniqueId . '">';
    
    // Barra de progreso oculta para animación
    $html .= '<div class="stars-progress-bg"></div>';
    
    // Estrellas llenas
    for ($i = 0; $i < $fullStars; $i++) {
        $delay = $animated ? ($i * 0.1) : 0;
        $html .= '<i class="fas fa-star star star-filled ' . $colorClass . '" style="animation-delay: ' . $delay . 's;"></i>';
    }
    
    // Media estrella
    if ($hasHalfStar) {
        $delay = $animated ? ($fullStars * 0.1) : 0;
        $html .= '<i class="fas fa-star-half-alt star star-half ' . $colorClass . '" style="animation-delay: ' . $delay . 's;"></i>';
    }
    
    // Estrellas vacías
    for ($i = 0; $i < $emptyStars; $i++) {
        $delay = $animated ? (($fullStars + ($hasHalfStar ? 1 : 0) + $i) * 0.1) : 0;
        $html .= '<i class="far fa-star star star-empty" style="animation-delay: ' . $delay . 's;"></i>';
    }
    
    $html .= '</div>';
    
    // Rating numérico con badge
    $html .= '<div class="rating-info">';
    if ($label) {
        $html .= '<span class="rating-label">' . $label . '</span>';
    }
    
    // Badge de rendimiento
    $badgeClass = '';
    if ($rating >= 4.0) {
        $badgeClass = 'badge-success';
    } elseif ($rating >= 3.0) {
        $badgeClass = 'badge-warning';  
    } else {
        $badgeClass = 'badge-danger';
    }
    
    $html .= '<span class="badge ' . $badgeClass . ' rating-badge">' . number_format($rating, 1) . '</span>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera barra de progreso circular para KPIs
 * @param float $percentage Porcentaje de 0 a 100
 * @param string $label Etiqueta
 * @param string $color Color del círculo
 * @return string HTML del círculo de progreso
 */
function generateCircularProgress($percentage, $label = '', $color = '#007bff') {
    $percentage = max(0, min(100, $percentage));
    $circumference = 2 * pi() * 45; // radio 45
    $offset = $circumference - ($percentage / 100) * $circumference;
    
    $html = '<div class="circular-progress-container">';
    $html .= '<div class="circular-progress">';
    $html .= '<svg class="progress-ring" width="100" height="100">';
    $html .= '<circle class="progress-ring-bg" cx="50" cy="50" r="45" stroke="#e9ecef" stroke-width="8" fill="none"/>';
    $html .= '<circle class="progress-ring-fill" cx="50" cy="50" r="45" stroke="' . $color . '" stroke-width="8" fill="none" 
              stroke-dasharray="' . $circumference . '" stroke-dashoffset="' . $offset . '" stroke-linecap="round"/>';
    $html .= '</svg>';
    $html .= '<div class="progress-text">';
    $html .= '<span class="progress-percentage">' . number_format($percentage, 0) . '%</span>';
    if ($label) {
        $html .= '<span class="progress-label">' . $label . '</span>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera indicador de tendencia con flecha
 * @param float $current Valor actual
 * @param float $previous Valor anterior (opcional)
 * @param string $format Formato de número
 * @return string HTML del indicador
 */
function generateTrendIndicator($current, $previous = null, $format = '%.1f') {
    $html = '<div class="trend-indicator">';
    
    if ($previous !== null && $previous > 0) {
        $change = $current - $previous;
        $percentChange = ($change / $previous) * 100;
        
        if ($percentChange > 0) {
            $html .= '<i class="fas fa-arrow-up text-success trend-arrow"></i>';
            $html .= '<span class="text-success">+' . number_format($percentChange, 1) . '%</span>';
        } elseif ($percentChange < 0) {
            $html .= '<i class="fas fa-arrow-down text-danger trend-arrow"></i>';
            $html .= '<span class="text-danger">' . number_format($percentChange, 1) . '%</span>';
        } else {
            $html .= '<i class="fas fa-minus text-muted trend-arrow"></i>';
            $html .= '<span class="text-muted">0%</span>';
        }
    }
    
    $html .= '<span class="current-value">' . sprintf($format, $current) . '</span>';
    $html .= '</div>';
    
    return $html;
}
@endphp

{{-- CSS para el sistema de estrellas mejorado --}}
<style>
/* Sistema de Estrellas Mejorado */
.enhanced-stars-container {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stars-wrapper {
    position: relative;
    display: flex;
    gap: 3px;
}

.stars-progress-bg {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255, 193, 7, 0.1) 100%);
    border-radius: 4px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stars-wrapper:hover .stars-progress-bg {
    opacity: 1;
}

/* Tamaños de estrellas */
.star-sm .star {
    font-size: 14px;
}

.star-md .star {
    font-size: 16px;
}

.star-lg .star {
    font-size: 20px;
}

/* Estados de estrellas */
.star {
    transition: all 0.3s ease;
    cursor: default;
}

.star-filled {
    color: #ffc107;
    text-shadow: 0 0 3px rgba(255, 193, 7, 0.3);
}

.star-half {
    color: #ffc107;
    text-shadow: 0 0 3px rgba(255, 193, 7, 0.3);
}

.star-empty {
    color: #dee2e6;
}

.star:hover {
    transform: scale(1.1);
}

/* Animaciones */
.stars-animated .star {
    opacity: 0;
    transform: scale(0) rotate(180deg);
    animation: starAppear 0.6s ease forwards;
}

@keyframes starAppear {
    0% {
        opacity: 0;
        transform: scale(0) rotate(180deg);
    }
    50% {
        opacity: 1;
        transform: scale(1.2) rotate(90deg);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

/* Rating Info */
.rating-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rating-label {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
}

.rating-badge {
    font-size: 11px;
    padding: 4px 8px;
    font-weight: 600;
    min-width: 35px;
    text-align: center;
}

/* Progreso Circular */
.circular-progress-container {
    display: inline-block;
    position: relative;
}

.circular-progress {
    position: relative;
    width: 100px;
    height: 100px;
}

.progress-ring {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.progress-ring-fill {
    transition: stroke-dashoffset 1s ease-in-out;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.progress-percentage {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #495057;
}

.progress-label {
    display: block;
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

/* Indicador de Tendencia */
.trend-indicator {
    display: flex;
    align-items: center;
    gap: 5px;
}

.trend-arrow {
    font-size: 12px;
}

.current-value {
    font-weight: 600;
    margin-left: 5px;
}

/* Tooltips mejorados */
.tooltip {
    font-size: 13px;
}

.tooltip-inner {
    background-color: rgba(0, 0, 0, 0.9);
    border-radius: 6px;
    padding: 8px 12px;
}

/* Responsive */
@media (max-width: 576px) {
    .enhanced-stars-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .star-md .star {
        font-size: 14px;
    }
    
    .star-lg .star {
        font-size: 16px;
    }
    
    .circular-progress {
        width: 80px;
        height: 80px;
    }
    
    .progress-percentage {
        font-size: 16px;
    }
}
</style>

{{-- JavaScript para inicializar tooltips y animaciones --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    $('[data-toggle="tooltip"]').tooltip({
        html: true,
        trigger: 'hover focus'
    });
    
    // Animar círculos de progreso cuando entran en vista
    const progressRings = document.querySelectorAll('.progress-ring-fill');
    
    if ('IntersectionObserver' in window) {
        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const ring = entry.target;
                    const finalOffset = ring.style.strokeDashoffset;
                    const circumference = ring.style.strokeDasharray;
                    
                    // Iniciar desde círculo completo
                    ring.style.strokeDashoffset = circumference;
                    
                    // Animar al valor final
                    setTimeout(() => {
                        ring.style.strokeDashoffset = finalOffset;
                    }, 100);
                    
                    progressObserver.unobserve(ring);
                }
            });
        }, { threshold: 0.5 });
        
        progressRings.forEach(ring => {
            progressObserver.observe(ring);
        });
    }
    
    // Efecto hover mejorado para estrellas
    document.querySelectorAll('.stars-wrapper').forEach(wrapper => {
        const stars = wrapper.querySelectorAll('.star');
        
        wrapper.addEventListener('mouseenter', function() {
            stars.forEach((star, index) => {
                star.style.animationDelay = (index * 0.05) + 's';
                star.classList.add('star-hover');
            });
        });
        
        wrapper.addEventListener('mouseleave', function() {
            stars.forEach(star => {
                star.classList.remove('star-hover');
            });
        });
    });
});
</script>