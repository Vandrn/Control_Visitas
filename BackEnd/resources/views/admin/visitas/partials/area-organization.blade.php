<!-- 
ARCHIVO: resources/views/admin/visitas/partials/area-organization.blade.php
Sistema de organización avanzada por áreas
-->

<!-- Filtros avanzados de galería -->
<div class="bg-white border-b border-gray-200 px-6 py-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
        <!-- Filtros de visualización -->
        <div class="flex items-center space-x-4">
            <!-- Filtro por tipo -->
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Mostrar:</label>
                <select x-model="filterType" 
                        @change="applyFilters()"
                        class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">Todas las áreas</option>
                    <option value="with-observations">Con observaciones</option>
                    <option value="without-observations">Sin observaciones</option>
                    <option value="recent">Más recientes</option>
                </select>
            </div>
            
            <!-- Ordenar por -->
            <div class="flex items-center space-x-2">
                <label class="text-sm font-medium text-gray-700">Ordenar:</label>
                <select x-model="sortBy" 
                        @change="applyFilters()"
                        class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="area">Por área</option>
                    <option value="size">Por tamaño</option>
                    <option value="name">Por nombre</option>
                </select>
            </div>
        </div>
        
        <!-- Estadísticas rápidas -->
        <div class="flex items-center space-x-6 text-sm text-gray-600">
            <div class="flex items-center space-x-1">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span x-text="imageStats.withObservations"></span>
                <span>con observaciones</span>
            </div>
            <div class="flex items-center space-x-1">
                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                <span x-text="imageStats.withoutObservations"></span>
                <span>sin observaciones</span>
            </div>
            <div class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span x-text="getTotalSize()"></span>
                <span>total</span>
            </div>
        </div>
    </div>
</div>

<!-- Pestañas mejoradas con contadores e iconos -->
<div class="border-b border-gray-200 bg-gray-50">
    <nav class="-mb-px flex space-x-1 px-6 overflow-x-auto">
        <!-- Pestaña "Todas" mejorada -->
        <button @click="activeTab = 'todas'" 
                :class="activeTab === 'todas' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-all duration-200 rounded-t-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Todas</span>
                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-blue-600 bg-blue-100 rounded-full">
                    {{ count($imagenes) }}
                </span>
            </div>
        </button>
        
        <!-- Pestañas por área con iconos contextuales -->
        @foreach($imagenes as $area => $imagen)
            <button @click="activeTab = '{{ $area }}'" 
                    :class="activeTab === '{{ $area }}' ? 'border-blue-500 text-blue-600 bg-white' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm transition-all duration-200 rounded-t-lg">
                <div class="flex items-center space-x-2">
                    <!-- Iconos específicos por área -->
                    @switch($area)
                        @case('operaciones')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            @break
                        @case('administracion')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            @break
                        @case('producto')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            @break
                        @case('personal')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            @break
                        @case('kpis')
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            @break
                        @default
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                    @endswitch
                    
                    <span>{{ $imagen['titulo'] }}</span>
                    
                    <!-- Badge con estado -->
                    <div class="flex items-center space-x-1">
                        @if(!empty($imagen['observaciones']))
                            <div class="w-2 h-2 bg-green-500 rounded-full" title="Con observaciones"></div>
                        @else
                            <div class="w-2 h-2 bg-gray-400 rounded-full" title="Sin observaciones"></div>
                        @endif
                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-gray-600 bg-gray-100 rounded-full">
                            1
                        </span>
                    </div>
                </div>
            </button>
        @endforeach
        
        <!-- Botón de configuración de vista -->
        <div class="flex items-center ml-auto">
            <div class="flex items-center space-x-2 px-4 py-3">
                <button @click="showConfig = !showConfig"
                        class="p-1 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V2m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
</div>

<!-- Panel de configuración expandible -->
<div x-show="showConfig" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 transform -translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-2"
     class="bg-gray-50 border-b border-gray-200 px-6 py-4">
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Configuración de vista -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Configuración de Vista</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="showImageInfo" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Mostrar información de imagen</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" x-model="showObservations" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Mostrar observaciones</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" x-model="enableLazyLoad" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Carga diferida (recomendado)</span>
                </label>
            </div>
        </div>
        
        <!-- Calidad de imagen -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Calidad de Imagen</label>
            <select x-model="imageQuality" class="w-full text-sm border border-gray-300 rounded-md px-3 py-2">
                <option value="thumbnail">Miniatura (rápida)</option>
                <option value="medium">Media (balanceada)</option>
                <option value="high">Alta (lenta)</option>
                <option value="original">Original (muy lenta)</option>
            </select>
        </div>
        
        <!-- Acciones rápidas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Acciones Rápidas</label>
            <div class="space-y-2">
                <button @click="refreshImages()" 
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    🔄 Recargar imágenes
                </button>
                <button @click="clearCache()" 
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    🗑️ Limpiar caché
                </button>
                <button @click="exportImageList()" 
                        class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
                    📋 Exportar lista
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones adicionales para galeriaComponent()
// AGREGAR estas funciones al objeto galeriaComponent() existente

const additionalGalleryFunctions = {
    // Datos adicionales
    filterType: 'all',
    sortBy: 'area',
    showConfig: false,
    showImageInfo: true,
    showObservations: true,
    enableLazyLoad: true,
    imageQuality: 'medium',
    
    imageStats: {
        withObservations: 0,
        withoutObservations: 0,
        totalSize: '0 MB'
    },
    
    // Funciones de filtrado
    applyFilters() {
        // Implementar lógica de filtrado
        console.log('Aplicando filtros:', this.filterType, this.sortBy);
        // Aquí iría la lógica para filtrar y ordenar imágenes
    },
    
    calculateImageStats() {
        const imagenes = @json($imagenes);
        let withObs = 0;
        let withoutObs = 0;
        
        Object.values(imagenes).forEach(imagen => {
            if (imagen.observaciones && imagen.observaciones.trim()) {
                withObs++;
            } else {
                withoutObs++;
            }
        });
        
        this.imageStats.withObservations = withObs;
        this.imageStats.withoutObservations = withoutObs;
    },
    
    getTotalSize() {
        // Estimación del tamaño total (simulado)
        const count = Object.keys(@json($imagenes)).length;
        const avgSize = 1.2; // MB promedio por imagen
        return `${(count * avgSize).toFixed(1)} MB`;
    },
    
    refreshImages() {
        // Recargar imágenes
        window.location.reload();
    },
    
    clearCache() {
        // Limpiar caché de imágenes
        if ('caches' in window) {
            caches.keys().then(names => {
                names.forEach(name => {
                    caches.delete(name);
                });
            });
        }
        this.$dispatch('cache-cleared');
    },
    
    exportImageList() {
        // Exportar lista de imágenes
        const imagenes = @json($imagenes);
        const data = Object.entries(imagenes).map(([area, imagen]) => ({
            area: imagen.titulo,
            url: imagen.url,
            hasObservations: !!(imagen.observaciones && imagen.observaciones.trim()),
            observations: imagen.observaciones || 'Sin observaciones'
        }));
        
        const csv = [
            ['Área', 'URL', 'Tiene Observaciones', 'Observaciones'],
            ...data.map(row => [row.area, row.url, row.hasObservations ? 'Sí' : 'No', row.observations])
        ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `imagenes_visita_{{ $infoVisita['id'] }}_{{ date('Y-m-d') }}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
};

// También agregar al init() del galeriaComponent():
// this.calculateImageStats();
</script>