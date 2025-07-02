{{-- 
    ARCHIVO: resources/views/admin/visitas/partials/mobile-optimization.blade.php
    OPTIMIZACIÓN MOBILE CON ESTADOS DE CARGA Y GESTOS TOUCH
--}}

@php
/**
 * Detecta si el dispositivo es móvil
 * @return bool
 */
function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
}

/**
 * Genera skeleton loader para mobile
 * @param string $type Tipo de skeleton: 'card', 'list', 'chart'
 * @param int $count Número de elementos
 * @return string HTML del skeleton
 */
function generateSkeletonLoader($type = 'card', $count = 3) {
    $html = '<div class="skeleton-container" id="skeletonLoader">';
    
    for ($i = 0; $i < $count; $i++) {
        switch ($type) {
            case 'card':
                $html .= '<div class="skeleton-card">';
                $html .= '<div class="skeleton-header">';
                $html .= '<div class="skeleton-avatar"></div>';
                $html .= '<div class="skeleton-title"></div>';
                $html .= '</div>';
                $html .= '<div class="skeleton-content">';
                $html .= '<div class="skeleton-line"></div>';
                $html .= '<div class="skeleton-line short"></div>';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'list':
                $html .= '<div class="skeleton-list-item">';
                $html .= '<div class="skeleton-icon"></div>';
                $html .= '<div class="skeleton-text">';
                $html .= '<div class="skeleton-line"></div>';
                $html .= '<div class="skeleton-line short"></div>';
                $html .= '</div>';
                $html .= '</div>';
                break;
                
            case 'chart':
                $html .= '<div class="skeleton-chart">';
                $html .= '<div class="skeleton-chart-header"></div>';
                $html .= '<div class="skeleton-chart-body">';
                for ($j = 0; $j < 5; $j++) {
                    $height = rand(30, 80);
                    $html .= '<div class="skeleton-bar" style="height: ' . $height . '%"></div>';
                }
                $html .= '</div>';
                $html .= '</div>';
                break;
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Genera indicador de carga específico para mobile
 * @param string $message Mensaje de carga
 * @param string $type Tipo: 'spinner', 'dots', 'pulse'
 * @return string HTML del loader
 */
function generateMobileLoader($message = 'Cargando...', $type = 'spinner') {
    $html = '<div class="mobile-loader-overlay" id="mobileLoader">';
    $html .= '<div class="mobile-loader-content">';
    
    switch ($type) {
        case 'spinner':
            $html .= '<div class="loader-spinner">';
            $html .= '<div class="spinner-ring"></div>';
            $html .= '<div class="spinner-ring"></div>';
            $html .= '<div class="spinner-ring"></div>';
            $html .= '</div>';
            break;
            
        case 'dots':
            $html .= '<div class="loader-dots">';
            $html .= '<div class="dot"></div>';
            $html .= '<div class="dot"></div>';
            $html .= '<div class="dot"></div>';
            $html .= '</div>';
            break;
            
        case 'pulse':
            $html .= '<div class="loader-pulse">';
            $html .= '<div class="pulse-circle"></div>';
            $html .= '</div>';
            break;
    }
    
    $html .= '<div class="loader-message">' . $message . '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Genera controles touch-friendly
 * @param array $controls Array de controles
 * @return string HTML de controles
 */
function generateTouchControls($controls) {
    $html = '<div class="touch-controls">';
    
    foreach ($controls as $control) {
        $html .= '<button class="touch-button ' . ($control['class'] ?? '') . '" ';
        $html .= 'data-action="' . ($control['action'] ?? '') . '" ';
        $html .= 'data-haptic="' . ($control['haptic'] ?? 'light') . '">';
        $html .= '<i class="' . ($control['icon'] ?? 'fa-circle') . '"></i>';
        $html .= '<span>' . ($control['label'] ?? 'Acción') . '</span>';
        $html .= '</button>';
    }
    
    $html .= '</div>';
    return $html;
}
@endphp

{{-- CSS para optimización mobile --}}
<style>
/* Skeleton Loaders */
.skeleton-container {
    padding: 20px;
    animation: fadeIn 0.3s ease-in;
}

.skeleton-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.skeleton-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-title {
    height: 20px;
    flex: 1;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    border-radius: 4px;
    animation: shimmer 1.5s infinite;
}

.skeleton-content {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.skeleton-line {
    height: 12px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    border-radius: 4px;
    animation: shimmer 1.5s infinite;
}

.skeleton-line.short {
    width: 60%;
}

.skeleton-list-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid #f1f3f4;
}

.skeleton-icon {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

.skeleton-text {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.skeleton-chart {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
}

.skeleton-chart-header {
    height: 24px;
    width: 40%;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    border-radius: 4px;
    margin-bottom: 20px;
    animation: shimmer 1.5s infinite;
}

.skeleton-chart-body {
    display: flex;
    align-items: end;
    gap: 8px;
    height: 100px;
}

.skeleton-bar {
    flex: 1;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    border-radius: 4px 4px 0 0;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Mobile Loader Overlay */
.mobile-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.mobile-loader-overlay.active {
    opacity: 1;
    visibility: visible;
}

.mobile-loader-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

/* Spinner Loader */
.loader-spinner {
    position: relative;
    width: 60px;
    height: 60px;
}

.spinner-ring {
    position: absolute;
    width: 100%;
    height: 100%;
    border: 3px solid transparent;
    border-top: 3px solid #007bff;
    border-radius: 50%;
    animation: spinnerRotate 1s linear infinite;
}

.spinner-ring:nth-child(2) {
    width: 80%;
    height: 80%;
    top: 10%;
    left: 10%;
    border-top-color: #28a745;
    animation-delay: -0.3s;
}

.spinner-ring:nth-child(3) {
    width: 60%;
    height: 60%;
    top: 20%;
    left: 20%;
    border-top-color: #ffc107;
    animation-delay: -0.6s;
}

@keyframes spinnerRotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Dots Loader */
.loader-dots {
    display: flex;
    gap: 8px;
}

.dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    animation: dotBounce 1.4s infinite ease-in-out;
}

.dot:nth-child(2) {
    animation-delay: -0.32s;
    background: #28a745;
}

.dot:nth-child(3) {
    animation-delay: -0.16s;
    background: #ffc107;
}

@key