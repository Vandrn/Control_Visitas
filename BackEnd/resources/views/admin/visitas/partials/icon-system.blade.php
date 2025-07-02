{{-- 
    ARCHIVO: resources/views/admin/visitas/partials/icon-system.blade.php
    SISTEMA DE ICONOGRAFÍA INTUITIVA CON ÍCONOS ESPECÍFICOS
--}}

@php
/**
 * Mapeo de íconos específicos por pregunta/concepto
 */
$questionIcons = [
    // Operaciones
    'pintura' => 'fa-paint-brush',
    'vitrinas' => 'fa-store-alt',
    'exhibicion' => 'fa-eye',
    'sala_ventas' => 'fa-shopping-bag',
    'aires' => 'fa-wind',
    'repisas' => 'fa-layer-group',
    'mueble_caja' => 'fa-cash-register',
    'equipo' => 'fa-desktop',
    'radio' => 'fa-music',
    'bodega' => 'fa-warehouse',
    'limpieza' => 'fa-broom',
    'comida' => 'fa-utensils',
    'bano' => 'fa-restroom',
    'sillas' => 'fa-chair',
    
    // Administración
    'cuenta_orden' => 'fa-file-invoice',
    'transferencias' => 'fa-exchange-alt',
    'remesas' => 'fa-money-bill-wave',
    'cuadre' => 'fa-balance-scale',
    'horarios' => 'fa-clock',
    'conteo' => 'fa-calculator',
    'pizarras' => 'fa-chalkboard',
    'files' => 'fa-folder-open',
    
    // Producto
    'nuevos_estilos' => 'fa-plus-circle',
    'etiquetas' => 'fa-tag',
    'precios' => 'fa-dollar-sign',
    'promociones' => 'fa-percentage',
    'reporte_80_20' => 'fa-chart-pie',
    'planogramas' => 'fa-sitemap',
    'exhibiciones' => 'fa-th-large',
    'sandalias' => 'fa-shoe-prints',
    
    // Personal
    'marcaciones' => 'fa-fingerprint',
    'uniforme' => 'fa-user-tie',
    'estandares' => 'fa-star',
    'amabilidad' => 'fa-smile',
    'bioseguridad' => 'fa-shield-alt',
    'disponibilidad' => 'fa-hands-helping',
    'ayuda_clientes' => 'fa-handshake',
    'tallas' => 'fa-ruler',
    'medir_pie' => 'fa-ruler-vertical',
    'ajuste_zapatos' => 'fa-check-circle',
    'elogios' => 'fa-thumbs-up',
    'atencion_caja' => 'fa-credit-card',
    'cursos' => 'fa-graduation-cap',
    'app_piso' => 'fa-mobile-alt',
    'app_inventario' => 'fa-inventory',
    
    // KPIs
    'venta' => 'fa-chart-line',
    'margen' => 'fa-percentage',
    'conversion' => 'fa-funnel-dollar',
    'upt' => 'fa-shopping-cart',
    'dpt' => 'fa-coins',
    'nps' => 'fa-heart',
    
    // Estados generales
    'excelente' => 'fa-trophy',
    'bueno' => 'fa-check',
    'regular' => 'fa-minus',
    'malo' => 'fa-times',
    'urgente' => 'fa-exclamation-triangle',
    'completado' => 'fa-check-circle',
    'pendiente' => 'fa-clock',
    'vencido' => 'fa-calendar-times'
];

/**
 * Obtiene ícono específico basado en palabra clave
 * @param string $keyword Palabra clave o contexto
 * @param string $fallback Ícono por defecto
 * @return string Clase del ícono FontAwesome
 */
function getContextualIcon($keyword, $fallback = 'fa-circle') {
    global $questionIcons;
    
    $keyword = strtolower($keyword);
    
    // Buscar coincidencia exacta
    if (isset($questionIcons[$keyword])) {
        return $questionIcons[$keyword];
    }
    
    // Buscar coincidencia parcial
    foreach ($questionIcons as $key => $icon) {
        if (strpos($keyword, $key) !== false || strpos($key, $keyword) !== false) {
            return $icon;
        }
    }
    
    return $fallback;
}

/**
 * Genera ícono de estado para planes de acción
 * @param int $diasRestantes Días restantes para vencimiento
 * @param bool $completado Si el plan está completado
 * @return array ['icon' => 'fa-icon', 'class' => 'text-color', 'animation' => 'css-class']
 */
function getPlanStatusIcon($diasRestantes, $completado = false) {
    if ($completado) {
        return [
            'icon' => 'fa-check-circle',
            'class' => 'text-success',
            'animation' => 'pulse-success'
        ];
    }
    
    if ($diasRestantes > 7) {
        return [
            'icon' => 'fa-calendar-check',
            'class' => 'text-success',
            'animation' => ''
        ];
    } elseif ($diasRestantes > 3) {
        return [
            'icon' => 'fa-calendar-alt',
            'class' => 'text-info',
            'animation' => ''
        ];
    } elseif ($diasRestantes > 0) {
        return [
            'icon' => 'fa-exclamation-triangle',
            'class' => 'text-warning',
            'animation' => 'pulse-warning'
        ];
    } elseif ($diasRestantes == 0) {
        return [
            'icon' => 'fa-bell',
            'class' => 'text-warning',
            'animation' => 'shake'
        ];
    } else {
        return [
            'icon' => 'fa-calendar-times',
            'class' => 'text-danger',
            'animation' => 'pulse-danger'
        ];
    }
}

/**
 * Genera ícono de urgencia basado en múltiples factores
 * @param array $factors Factores de urgencia
 * @return string HTML del indicador de urgencia
 */
function generateUrgencyIndicator($factors) {
    $urgencyLevel = 0;
    $reasons = [];
    
    // Evaluar factores
    if ($factors['promedio_bajo'] ?? false) {
        $urgencyLevel += 3;
        $reasons[] = 'Evaluación baja';
    }
    
    if ($factors['planes_vencidos'] ?? 0 > 0) {
        $urgencyLevel += 2;
        $reasons[] = 'Planes vencidos';
    }
    
    if ($factors['kpis_criticos'] ?? 0 > 2) {
        $urgencyLevel += 2;
        $reasons[] = 'KPIs críticos';
    }
    
    if ($factors['sin_seguimiento'] ?? false) {
        $urgencyLevel += 1;
        $reasons[] = 'Falta seguimiento';
    }
    
    if ($urgencyLevel == 0) {
        return '';
    }
    
    $html = '<div class="urgency-indicator urgency-level-' . min($urgencyLevel, 5) . '">';
    
    if ($urgencyLevel >= 5) {
        $html .= '<i class="fas fa-exclamation-triangle urgency-critical"></i>';
        $html .= '<span class="urgency-label">Crítico</span>';
    } elseif ($urgencyLevel >= 3) {
        $html .= '<i class="fas fa-exclamation-circle urgency-high"></i>';
        $html .= '<span class="urgency-label">Alto</span>';
    } else {
        $html .= '<i class="fas fa-info-circle urgency-medium"></i>';
        $html .= '<span class="urgency-label">Medio</span>';
    }
    
    $html .= '<div class="urgency-tooltip">';
    $html .= '<strong>Requiere atención:</strong><br>';
    $html .= implode('<br>', $reasons);
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera íconos de progreso para secciones
 * @param float $progress Progreso de 0 a 1
 * @param string $section Nombre de la sección
 * @return string HTML del indicador de progreso
 */
function generateProgressIcon($progress, $section = '') {
    $percentage = $progress * 100;
    
    $html = '<div class="progress-icon-container">';
    $html .= '<div class="progress-icon" data-progress="' . $percentage . '">';
    
    if ($percentage >= 90) {
        $html .= '<i class="fas fa-check-circle text-success"></i>';
    } elseif ($percentage >= 70) {
        $html .= '<i class="fas fa-check text-info"></i>';
    } elseif ($percentage >= 50) {
        $html .= '<i class="fas fa-minus text-warning"></i>';
    } else {
        $html .= '<i class="fas fa-times text-danger"></i>';
    }
    
    $html .= '<div class="progress-ring">';
    $html .= '<svg width="30" height="30">';
    $html .= '<circle cx="15" cy="15" r="12" stroke="#e9ecef" stroke-width="2" fill="none"/>';
    $html .= '<circle cx="15" cy="15" r="12" stroke="currentColor" stroke-width="2" fill="none" 
              stroke-dasharray="' . (2 * pi() * 12) . '" 
              stroke-dashoffset="' . (2 * pi() * 12 * (1 - $progress)) . '" 
              stroke-linecap="round"/>';
    $html .= '</svg>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    if ($section) {
        $html .= '<span class="progress-section-name">' . $section . '</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}
@endphp

{{-- CSS para el sistema de iconografía --}}
<style>
/* Íconos Contextuales Mejorados */
.contextual-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    margin-right: 8px;
    font-size: 12px;
    transition: all 0.3s ease;
}

.contextual-icon.icon-excellent {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.contextual-icon.icon-good {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
    color: white;
}

.contextual-icon.icon-regular {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
}

.contextual-icon.icon-poor {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
    color: white;
}

/* Íconos de Estado de Planes */
.plan-status-icon {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    font-weight: 500;
}

/* Animaciones de Estado */
.pulse-success {
    animation: pulseSuccess 2s infinite;
}

.pulse-warning {
    animation: pulseWarning 1.5s infinite;
}

.pulse-danger {
    animation: pulseDanger 1s infinite;
}

.shake {
    animation: shake 0.5s infinite;
}

@keyframes pulseSuccess {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes pulseWarning {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.8; transform: scale(1.1); }
}

@keyframes pulseDanger {
    0%, 100% { opacity: 1; transform: scale(1); }
    25% { opacity: 0.8; transform: scale(1.05); }
    75% { opacity: 0.9; transform: scale(0.95); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

/* Indicadores de Urgencia */
.urgency-indicator {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
}

.urgency-level-1, .urgency-level-2 {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
    border: 1px solid rgba(23, 162, 184, 0.3);
}

.urgency-level-3, .urgency-level-4 {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
    border: 1px solid rgba(255, 193, 7, 0.3);
}

.urgency-level-5 {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

.urgency-critical {
    animation: pulseDanger 1s infinite;
}

.urgency-high {
    animation: pulseWarning 1.5s infinite;
}

.urgency-tooltip {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 11px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
    margin-top: 5px;
}

.urgency-indicator:hover .urgency-tooltip {
    opacity: 1;
    visibility: visible;
}

/* Íconos de Progreso */
.progress-icon-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.progress-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
}

.progress-icon i {
    position: relative;
    z-index: 2;
    font-size: 16px;
}

.progress-ring {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.progress-ring svg {
    transform: rotate(-90deg);
}

.progress-ring circle:last-child {
    transition: stroke-dashoffset 1s ease-in-out;
}

.progress-section-name {
    font-size: 10px;
    font-weight: 500;
    color: #6c757d;
    text-align: center;
}

/* Íconos Específicos por Categoría */
.section-icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.category-icon {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.category-icon:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.category-icon i {
    font-size: 24px;
    margin-bottom: 8px;
    color: #007bff;
}

.category-icon.operaciones i { color: #007bff; }
.category-icon.administracion i { color: #28a745; }
.category-icon.producto i { color: #ffc107; }
.category-icon.personal i { color: #17a2b8; }
.category-icon.kpis i { color: #6f42c1; }

.category-icon span {
    font-size: 11px;
    font-weight: 500;
    text-align: center;
    color: #495057;
}

/* Estados de Evaluación con Íconos */
.evaluation-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.evaluation-status.status-5 {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.evaluation-status.status-4 {
    background: linear-gradient(135deg, #17a2b8, #6f42c1);
    color: white;
}

.evaluation-status.status-3 {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: #212529;
}

.evaluation-status.status-2 {
    background: linear-gradient(135deg, #fd7e14, #dc3545);
    color: white;
}

.evaluation-status.status-1 {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
    color: white;
}

/* Íconos de Notificación */
.notification-icon {
    position: relative;
    display: inline-block;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    animation: notificationPulse 2s infinite;
}

@keyframes notificationPulse {
    0%, 100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.2);
        opacity: 0.8;
    }
}

/* Íconos de Acción Rápida */
.quick-action-icons {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin: 15px 0;
}

.quick-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    color: #6c757d;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 16px;
}

.quick-action:hover {
    background: #007bff;
    border-color: #007bff;
    color: white;
    text-decoration: none;
    transform: scale(1.1);
}

.quick-action.action-edit:hover {
    background: #28a745;
    border-color: #28a745;
}

.quick-action.action-delete:hover {
    background: #dc3545;
    border-color: #dc3545;
}

.quick-action.action-download:hover {
    background: #17a2b8;
    border-color: #17a2b8;
}

/* Indicadores de Tendencia con Íconos */
.trend-icon {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
}

.trend-up {
    color: #28a745;
}

.trend-down {
    color: #dc3545;
}

.trend-stable {
    color: #6c757d;
}

.trend-icon i {
    font-size: 10px;
}

/* Íconos de Categorías en Preguntas */
.question-with-icon {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.question-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: rgba(0, 123, 255, 0.1);
    color: #007bff;
    font-size: 14px;
    flex-shrink: 0;
}

.question-content {
    flex: 1;
}

.question-title {
    font-size: 14px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.question-rating-with-icon {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
}

/* Responsive Design para Íconos */
@media (max-width: 768px) {
    .section-icon-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }
    
    .category-icon {
        padding: 10px;
    }
    
    .category-icon i {
        font-size: 20px;
    }
    
    .quick-action-icons {
        gap: 8px;
    }
    
    .quick-action {
        width: 35px;
        height: 35px;
        font-size: 14px;
    }
}

@media (max-width: 576px) {
    .question-with-icon {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 8px;
    }
    
    .question-rating-with-icon {
        flex-direction: column;
        gap: 10px;
    }
    
    .section-icon-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .urgency-tooltip {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        white-space: normal;
        max-width: 250px;
    }
}

/* Efectos Especiales para Íconos Críticos */
.critical-attention {
    position: relative;
    overflow: visible;
}

.critical-attention::before {
    content: '';
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    background: linear-gradient(45deg, #dc3545, #e83e8c, #dc3545);
    border-radius: inherit;
    z-index: -1;
    animation: criticalGlow 1.5s infinite;
}

@keyframes criticalGlow {
    0%, 100% {
        opacity: 0.3;
        transform: scale(1);
    }
    50% {
        opacity: 0.6;
        transform: scale(1.05);
    }
}

/* Íconos con Tooltip Avanzado */
.icon-with-tooltip {
    position: relative;
    cursor: help;
}

.advanced-tooltip {
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #343a40, #495057);
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.4;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
    margin-bottom: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.advanced-tooltip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #343a40;
}

.icon-with-tooltip:hover .advanced-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-5px);
}

/* Estados de Carga para Íconos */
.icon-loading {
    animation: iconSpin 1s linear infinite;
}

@keyframes iconSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Íconos de Estado Mejorados */
.status-icon-enhanced {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    font-size: 12px;
    font-weight: bold;
    position: relative;
    overflow: hidden;
}

.status-icon-enhanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s ease;
}

.status-icon-enhanced:hover::before {
    left: 100%;
}
</style>

{{-- JavaScript para funcionalidades de iconografía --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips para íconos
    $('.icon-with-tooltip').each(function() {
        const $icon = $(this);
        const tooltipText = $icon.data('tooltip') || 'Información adicional';
        
        if (!$icon.find('.advanced-tooltip').length) {
            $icon.append(`<div class="advanced-tooltip">${tooltipText}</div>`);
        }
    });
    
    // Animar íconos de progreso cuando entran en vista
    const progressIcons = document.querySelectorAll('.progress-icon');
    
    if ('IntersectionObserver' in window) {
        const iconObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const icon = entry.target;
                    const progress = parseFloat(icon.dataset.progress) || 0;
                    const circle = icon.querySelector('.progress-ring circle:last-child');
                    
                    if (circle) {
                        const circumference = 2 * Math.PI * 12;
                        const offset = circumference - (progress / 100) * circumference;
                        
                        circle.style.strokeDashoffset = circumference;
                        setTimeout(() => {
                            circle.style.strokeDashoffset = offset;
                        }, 100);
                    }
                    
                    iconObserver.unobserve(icon);
                }
            });
        }, { threshold: 0.5 });
        
        progressIcons.forEach(icon => {
            iconObserver.observe(icon);
        });
    }
    
    // Efectos hover para categorías de íconos
    document.querySelectorAll('.category-icon').forEach(icon => {
        icon.addEventListener('mouseenter', function() {
            const iconElement = this.querySelector('i');
            iconElement.style.transform = 'scale(1.2) rotate(5deg)';
        });
        
        icon.addEventListener('mouseleave', function() {
            const iconElement = this.querySelector('i');
            iconElement.style.transform = 'scale(1) rotate(0deg)';
        });
    });
    
    // Sistema de notificaciones con íconos
    function updateNotificationBadges() {
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            const count = parseInt(badge.textContent) || 0;
            if (count > 0) {
                badge.style.display = 'flex';
                badge.style.animation = 'notificationPulse 2s infinite';
            } else {
                badge.style.display = 'none';
            }
        });
    }
    
    // Actualizar badges cada 30 segundos
    setInterval(updateNotificationBadges, 30000);
    updateNotificationBadges();
    
    // Click handlers para acciones rápidas
    document.querySelectorAll('.quick-action').forEach(action => {
        action.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Efecto de click
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1.1)';
            }, 100);
            
            // Aquí se implementarían las acciones específicas
            const actionType = this.classList.contains('action-edit') ? 'edit' :
                              this.classList.contains('action-delete') ? 'delete' :
                              this.classList.contains('action-download') ? 'download' : 'view';
            
            console.log(`Acción ejecutada: ${actionType}`);
        });
    });
    
    // Generar íconos contextuales dinámicamente
    function generateContextualIcons() {
        const questions = document.querySelectorAll('.question-text');
        
        questions.forEach(question => {
            const text = question.textContent.toLowerCase();
            let iconClass = 'fa-circle';
            
            // Mapeo simple de palabras clave a íconos
            if (text.includes('pintura')) iconClass = 'fa-paint-brush';
            else if (text.includes('vitrina')) iconClass = 'fa-store-alt';
            else if (text.includes('limpi')) iconClass = 'fa-broom';
            else if (text.includes('equipo')) iconClass = 'fa-desktop';
            else if (text.includes('personal')) iconClass = 'fa-users';
            else if (text.includes('precio')) iconClass = 'fa-dollar-sign';
            else if (text.includes('inventario')) iconClass = 'fa-inventory';
            
            // Agregar ícono si no existe
            if (!question.previousElementSibling?.classList.contains('question-icon')) {
                const iconDiv = document.createElement('div');
                iconDiv.className = 'question-icon';
                iconDiv.innerHTML = `<i class="fas ${iconClass}"></i>`;
                question.parentNode.insertBefore(iconDiv, question);
                question.parentNode.classList.add('question-with-icon');
            }
        });
    }
    
    // Ejecutar generación de íconos
    generateContextualIcons();
    
    // Efecto de carga para íconos críticos
    document.querySelectorAll('.urgency-critical').forEach(icon => {
        setInterval(() => {
            icon.classList.add('icon-loading');
            setTimeout(() => {
                icon.classList.remove('icon-loading');
            }, 500);
        }, 3000);
    });
});
</script>