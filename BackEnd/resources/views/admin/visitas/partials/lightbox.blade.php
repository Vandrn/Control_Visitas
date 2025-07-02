{{-- 
ARCHIVO: resources/views/admin/visitas/partials/lightbox.blade.php
INCLUIR al final de imagenes.blade.php antes de </div> final
--}}

<!-- Lightbox Modal -->
<div x-show="lightboxOpen" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 overflow-hidden"
     style="display: none;"
     @click="closeLightbox()"
     @keydown.escape.window="closeLightbox()">
    
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-90"></div>
    
    <!-- Modal Content -->
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative max-w-7xl w-full"
             @click.stop
             x-data="{ 
                 imageLoaded: false,
                 imageError: false,
                 zoomLevel: 1,
                 panX: 0,
                 panY: 0,
                 isDragging: false,
                 startX: 0,
                 startY: 0,
                 lastPanX: 0,
                 lastPanY: 0
             }">
            
            <!-- Loading State -->
            <div x-show="!imageLoaded && !imageError" 
                 class="flex items-center justify-center h-96">
                <div class="flex flex-col items-center space-y-4">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-white border-t-transparent"></div>
                    <p class="text-white text-sm">Cargando imagen...</p>
                </div>
            </div>
            
            <!-- Error State -->
            <div x-show="imageError" 
                 class="flex items-center justify-center h-96">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <p class="text-white mt-2">Error al cargar la imagen</p>
                </div>
            </div>
            
            <!-- Imagen Principal -->
            <div x-show="imageLoaded" 
                 class="relative overflow-hidden rounded-lg bg-gray-900"
                 style="touch-action: none;">
                
                <img :src="currentImage.url" 
                     :alt="currentImage.titulo"
                     class="max-w-full max-h-[80vh] mx-auto object-contain transition-transform duration-200 select-none"
                     :style="`transform: scale(${zoomLevel}) translate(${panX}px, ${panY}px)`"
                     @load="imageLoaded = true; imageError = false"
                     @error="imageError = true; imageLoaded = false"
                     @mousedown="startDrag($event)"
                     @mousemove="drag($event)"
                     @mouseup="endDrag()"
                     @mouseleave="endDrag()"
                     @wheel="zoom($event)"
                     @touchstart="startTouch($event)"
                     @touchmove="moveTouch($event)"
                     @touchend="endTouch($event)"
                     draggable="false">
            </div>
            
            <!-- Header con información -->
            <div x-show="imageLoaded" 
                 class="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/70 to-transparent p-6">
                <div class="flex items-center justify-between text-white">
                    <div>
                        <h3 class="text-lg font-semibold" x-text="currentImage.titulo"></h3>
                        <p class="text-sm opacity-75">{{ $infoVisita['tienda'] }} • {{ $infoVisita['pais'] }}</p>
                    </div>
                    
                    <!-- Navegación entre imágenes -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm opacity-75" x-text="`${currentImageIndex + 1} de ${totalImages}`"></span>
                    </div>
                </div>
            </div>
            
            <!-- Controles de zoom -->
            <div x-show="imageLoaded" 
                 class="absolute bottom-20 left-1/2 transform -translate-x-1/2 bg-black/70 rounded-full px-4 py-2">
                <div class="flex items-center space-x-4 text-white">
                    <button @click="zoomOut()" 
                            :disabled="zoomLevel <= 0.5"
                            class="p-2 hover:bg-white/10 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    </button>
                    
                    <span class="text-sm font-medium min-w-[60px] text-center" x-text="`${Math.round(zoomLevel * 100)}%`"></span>
                    
                    <button @click="zoomIn()" 
                            :disabled="zoomLevel >= 3"
                            class="p-2 hover:bg-white/10 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </button>
                    
                    <div class="w-px h-6 bg-white/30"></div>
                    
                    <button @click="resetZoom()" 
                            class="p-2 hover:bg-white/10 rounded-full transition-colors text-sm">
                        Reset
                    </button>
                </div>
            </div>
            
            <!-- Barra de herramientas inferior -->
            <div x-show="imageLoaded" 
                 class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-6">
                <div class="flex items-center justify-between">
                    <!-- Navegación -->
                    <div class="flex items-center space-x-2">
                        <button @click="previousImage()" 
                                :disabled="currentImageIndex === 0"
                                class="p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        
                        <button @click="nextImage()" 
                                :disabled="currentImageIndex === totalImages - 1"
                                class="p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="flex items-center space-x-2">
                        <!-- Información -->
                        <button @click="toggleInfo()" 
                                class="p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                        
                        <!-- Descargar -->
                        <button @click="downloadCurrentImage()" 
                                class="p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </button>
                        
                        <!-- Cerrar -->
                        <button @click="closeLightbox()" 
                                class="p-3 bg-white/10 hover:bg-white/20 rounded-full text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Panel de información (toggle) -->
            <div x-show="showInfo && imageLoaded" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform translate-x-full"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-full"
                 class="absolute top-0 right-0 h-full w-80 bg-black/90 p-6 overflow-y-auto">
                
                <div class="text-white space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-lg font-semibold">Información</h4>
                        <button @click="toggleInfo()" 
                                class="p-1 hover:bg-white/10 rounded">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-300 mb-1">Área:</p>
                            <p x-text="currentImage.titulo"></p>
                        </div>
                        
                        <div>
                            <p class="text-gray-300 mb-1">Tienda:</p>
                            <p>{{ $infoVisita['tienda'] }}</p>
                        </div>
                        
                        <div>
                            <p class="text-gray-300 mb-1">Evaluador:</p>
                            <p>{{ $infoVisita['evaluador'] }}</p>
                        </div>
                        
                        <div>
                            <p class="text-gray-300 mb-1">Fecha:</p>
                            <p>{{ \Carbon\Carbon::parse($infoVisita['fecha'])->format('d/m/Y H:i') }}</p>
                        </div>
                        
                        <div x-show="currentImage.observaciones">
                            <p class="text-gray-300 mb-1">Observaciones:</p>
                            <p class="leading-relaxed" x-text="currentImage.observaciones"></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Instrucciones para móvil -->
            <div x-show="imageLoaded" 
                 class="absolute top-20 left-1/2 transform -translate-x-1/2 text-white text-center opacity-75 pointer-events-none lg:hidden">
                <p class="text-sm">Pellizca para hacer zoom • Arrastra para mover</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para el lightbox */
.lightbox-image {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
}

/* Prevenir scroll del body cuando el lightbox está abierto */
body.lightbox-open {
    overflow: hidden;
}

/* Mejorar rendimiento en móviles */
.lightbox-container {
    transform: translateZ(0);
    -webkit-transform: translateZ(0);
}

/* Touch optimizations */
@media (hover: none) and (pointer: coarse) {
    .lightbox-controls button {
        min-height: 44px;
        min-width: 44px;
    }
}
</style>