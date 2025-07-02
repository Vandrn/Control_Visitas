{{-- 
ARCHIVO: resources/views/admin/visitas/partials/lazy-loading.blade.php
SISTEMA DE LAZY LOADING Y PERFORMANCE
--}}

<!-- Intersection Observer para Lazy Loading -->
<script>
// Sistema de Lazy Loading con Intersection Observer
class LazyImageLoader {
    constructor() {
        this.imageObserver = null;
        this.loadingImages = new Set();
        this.init();
    }

    init() {
        // Verificar soporte de Intersection Observer
        if ('IntersectionObserver' in window) {
            this.imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px', // Comenzar carga 50px antes
                threshold: 0.01
            });
        }

        this.observeImages();
        this.addErrorHandling();
    }

    observeImages() {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        if (this.imageObserver) {
            lazyImages.forEach(img => {
                this.imageObserver.observe(img);
            });
        } else {
            // Fallback para navegadores sin soporte
            lazyImages.forEach(img => {
                this.loadImage(img);
            });
        }
    }

    loadImage(img) {
        if (this.loadingImages.has(img)) return;
        
        this.loadingImages.add(img);
        const src = img.dataset.src;
        const placeholder = img.dataset.placeholder;
        
        // Mostrar skeleton mientras carga
        this.showSkeleton(img);
        
        // Crear nueva imagen para precargar
        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            // Transición suave
            img.style.opacity = '0';
            img.src = src;
            img.removeAttribute('data-src');
            
            // Animar entrada
            setTimeout(() => {
                img.style.transition = 'opacity 0.3s ease-in-out';
                img.style.opacity = '1';
                this.hideSkeleton(img);
            }, 50);
            
            this.loadingImages.delete(img);
        };
        
        imageLoader.onerror = () => {
            this.handleImageError(img);
            this.loadingImages.delete(img);
        };
        
        imageLoader.src = src;
    }

    showSkeleton(img) {
        const skeleton = img.parentElement.querySelector('.image-skeleton');
        if (skeleton) {
            skeleton.style.display = 'block';
        }
    }

    hideSkeleton(img) {
        const skeleton = img.parentElement.querySelector('.image-skeleton');
        if (skeleton) {
            skeleton.style.display = 'none';
        }
    }

    handleImageError(img) {
        img.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDIwMCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik03NS4wIDc1LjBIMTI1LjBWODUuMEg3NS4wVjc1LjBaIiBmaWxsPSIjOUI5QkEwIi8+CjxwYXRoIGQ9Ik04NS4wIDY1LjBIOTUuMFY5NS4wSDg1LjBWNjUuMFoiIGZpbGw9IiM5QjlCQTAiLz4KPHRleHQgeD0iMTAwIiB5PSIxMTAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzlCOUJBMCI+SW1hZ2VuIG5vIGRpc3BvbmlibGU8L3RleHQ+Cjwvc3ZnPgo=';
        img.alt = 'Imagen no disponible';
        img.classList.add('error-image');
        this.hideSkeleton(img);
    }

    addErrorHandling() {
        // Manejar errores de carga existentes
        document.querySelectorAll('img').forEach(img => {
            if (!img.complete && img.naturalHeight === 0) {
                img.addEventListener('error', () => {
                    this.handleImageError(img);
                });
            }
        });
    }

    // Método público para reobservar imágenes dinámicas
    refresh() {
        this.observeImages();
    }
}

// Thumbnails con diferentes calidades
class ThumbnailManager {
    constructor() {
        this.sizes = {
            thumb: '_thumb_150x150',
            medium: '_medium_400x300',
            large: '_large_800x600'
        };
    }

    generateSrcSet(originalUrl) {
        const baseUrl = originalUrl.replace(/\.[^/.]+$/, '');
        const extension = originalUrl.split('.').pop();
        
        return `
            ${baseUrl}${this.sizes.thumb}.${extension} 150w,
            ${baseUrl}${this.sizes.medium}.${extension} 400w,
            ${baseUrl}${this.sizes.large}.${extension} 800w
        `;
    }

    getSizes() {
        return `
            (max-width: 576px) 150px,
            (max-width: 768px) 400px,
            800px
        `;
    }
}

// Preloader para próximas imágenes
class ImagePreloader {
    constructor() {
        this.preloadCache = new Map();
        this.maxCacheSize = 10;
    }

    preloadNext(urls) {
        urls.slice(0, 3).forEach(url => { // Precargar solo 3 siguientes
            if (!this.preloadCache.has(url)) {
                const img = new Image();
                img.src = url;
                
                this.preloadCache.set(url, img);
                
                // Limpiar caché si está muy grande
                if (this.preloadCache.size > this.maxCacheSize) {
                    const firstKey = this.preloadCache.keys().next().value;
                    this.preloadCache.delete(firstKey);
                }
            }
        });
    }
}

// Monitor de performance
class PerformanceMonitor {
    constructor() {
        this.metrics = {
            imagesLoaded: 0,
            totalImages: 0,
            loadTimes: [],
            errorCount: 0
        };
    }

    trackImageLoad(startTime) {
        this.metrics.imagesLoaded++;
        this.metrics.loadTimes.push(Date.now() - startTime);
        this.updateProgress();
    }

    trackImageError() {
        this.metrics.errorCount++;
        this.updateProgress();
    }

    updateProgress() {
        const progress = (this.metrics.imagesLoaded + this.metrics.errorCount) / this.metrics.totalImages * 100;
        
        // Actualizar barra de progreso si existe
        const progressBar = document.querySelector('.gallery-progress');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            
            if (progress >= 100) {
                setTimeout(() => {
                    progressBar.parentElement.style.display = 'none';
                }, 1000);
            }
        }
    }

    getAverageLoadTime() {
        if (this.metrics.loadTimes.length === 0) return 0;
        return this.metrics.loadTimes.reduce((a, b) => a + b, 0) / this.metrics.loadTimes.length;
    }
}

// Inicializar sistemas
let lazyLoader, thumbnailManager, imagePreloader, performanceMonitor;

document.addEventListener('DOMContentLoaded', function() {
    lazyLoader = new LazyImageLoader();
    thumbnailManager = new ThumbnailManager();
    imagePreloader = new ImagePreloader();
    performanceMonitor = new PerformanceMonitor();
    
    // Contar imágenes totales
    performanceMonitor.metrics.totalImages = document.querySelectorAll('img[data-src]').length;
});

// Exportar para uso global
window.galleryOptimization = {
    lazyLoader,
    thumbnailManager,
    imagePreloader,
    performanceMonitor
};
</script>

<!-- CSS para Skeleton Loading y Optimizaciones -->
<style>
/* Skeleton Loading */
.image-skeleton {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 2s infinite;
    border-radius: 0.5rem;
    display: none;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Estados de carga */
.image-loading {
    opacity: 0.7;
    filter: blur(2px);
    transition: all 0.3s ease;
}

.image-loaded {
    opacity: 1;
    filter: none;
}

.error-image {
    opacity: 0.6;
    filter: grayscale(100%);
}

/* Optimizaciones de performance */
.gallery-container {
    contain: layout style paint;
    will-change: scroll-position;
}

.gallery-item {
    contain: layout style paint;
    transform: translateZ(0); /* Crear capa de composición */
}

.gallery-item img {
    transition: transform 0.2s ease;
    will-change: transform;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

/* Barra de progreso */
.gallery-progress-container {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.gallery-progress {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #10b981);
    width: 0%;
    transition: width 0.3s ease;
}

/* Optimizaciones móviles */
@media (max-width: 768px) {
    .gallery-item {
        /* Reducir efectos en móviles para mejor performance */
        transition: none;
    }
    
    .gallery-item:hover img {
        transform: none;
    }
    
    /* Optimizar para touch */
    .gallery-item {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
}

/* Estados de conexión */
.offline-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #ef4444;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    z-index: 1001;
    display: none;
}

.offline-indicator.show {
    display: block;
}

/* Animaciones de entrada progresivas */
.gallery-item {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}

.gallery-item:nth-child(1) { animation-delay: 0.1s; }
.gallery-item:nth-child(2) { animation-delay: 0.2s; }
.gallery-item:nth-child(3) { animation-delay: 0.3s; }
.gallery-item:nth-child(4) { animation-delay: 0.4s; }
.gallery-item:nth-child(n+5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Scroll suave */
html {
    scroll-behavior: smooth;
}

/* Reducir animaciones si el usuario lo prefiere */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
    
    .gallery-item {
        animation: none;
        opacity: 1;
        transform: none;
    }
}
</style>

<!-- Detector de conexión -->
<script>
// Monitor de conexión de red
class NetworkMonitor {
    constructor() {
        this.isOnline = navigator.onLine;
        this.indicator = null;
        this.init();
    }

    init() {
        this.createIndicator();
        this.bindEvents();
        this.updateStatus();
    }

    createIndicator() {
        this.indicator = document.createElement('div');
        this.indicator.className = 'offline-indicator';
        this.indicator.textContent = 'Sin conexión a internet';
        document.body.appendChild(this.indicator);
    }

    bindEvents() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.updateStatus();
            this.retryFailedImages();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.updateStatus();
        });
    }

    updateStatus() {
        if (this.isOnline) {
            this.indicator.classList.remove('show');
        } else {
            this.indicator.classList.add('show');
        }
    }

    retryFailedImages() {
        const failedImages = document.querySelectorAll('.error-image');
        failedImages.forEach(img => {
            if (img.dataset.originalSrc) {
                img.classList.remove('error-image');
                img.src = img.dataset.originalSrc;
            }
        });
    }
}

// Inicializar monitor de red
document.addEventListener('DOMContentLoaded', function() {
    new NetworkMonitor();
});
</script>