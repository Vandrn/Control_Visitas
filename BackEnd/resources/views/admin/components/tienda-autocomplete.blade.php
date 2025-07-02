<!-- Campo de búsqueda de tienda con autocomplete -->
<div class="mb-4" x-data="tiendaAutocomplete()">
    <label for="tienda" class="block text-sm font-medium text-gray-700 mb-1">
        Buscar Tienda
    </label>
    <div class="relative">
        <input type="text" 
               id="tienda" 
               name="tienda" 
               x-model="searchTerm"
               @input="filterTiendas()"
               @focus="showDropdown = true"
               @keydown.arrow-down.prevent="navigateDown()"
               @keydown.arrow-up.prevent="navigateUp()"
               @keydown.enter.prevent="selectCurrent()"
               @keydown.escape="hideDropdown()"
               value="{{ $filtros['tienda'] ?? '' }}"
               placeholder="Escriba el nombre de la tienda..."
               autocomplete="off"
               class="w-full px-3 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
        
        <!-- Icono de búsqueda -->
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
        
        <!-- Spinner de carga -->
        <div x-show="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
        
        <!-- Dropdown de sugerencias -->
        <div x-show="showDropdown && filteredTiendas.length > 0" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
            
            <template x-for="(tienda, index) in filteredTiendas.slice(0, 10)" :key="tienda">
                <div @click="selectTienda(tienda)" 
                     :class="index === selectedIndex ? 'bg-blue-600 text-white' : 'text-gray-900 hover:bg-gray-100'"
                     class="cursor-pointer select-none relative py-2 pl-3 pr-9 transition-colors duration-150">
                    <span class="block truncate" x-html="highlightMatch(tienda, searchTerm)"></span>
                </div>
            </template>
            
            <!-- Sin resultados -->
            <div x-show="filteredTiendas.length === 0 && searchTerm.length > 0" 
                 class="cursor-default select-none relative py-2 pl-3 pr-9 text-gray-700">
                <span class="block truncate">No se encontraron tiendas</span>
            </div>
        </div>
    </div>
    
    <!-- Contador de resultados -->
    <div x-show="searchTerm.length > 0" class="mt-1 text-xs text-gray-500">
        <span x-text="filteredTiendas.length"></span> tienda<span x-show="filteredTiendas.length !== 1">s</span> encontrada<span x-show="filteredTiendas.length !== 1">s</span>
    </div>
</div>

<script>
function tiendaAutocomplete() {
    return {
        searchTerm: '{{ $filtros['tienda'] ?? '' }}',
        allTiendas: [],
        filteredTiendas: [],
        showDropdown: false,
        selectedIndex: -1,
        loading: false,
        
        async init() {
            await this.loadTiendas();
            this.filterTiendas();
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.hideDropdown();
                }
            });
        },
        
        async loadTiendas() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.api.tiendas") }}');
                const data = await response.json();
                
                if (data.success) {
                    this.allTiendas = data.tiendas;
                    console.log(`Cargadas ${data.total} tiendas`);
                } else {
                    console.error('Error al cargar tiendas:', data.error);
                }
            } catch (error) {
                console.error('Error de red al cargar tiendas:', error);
            } finally {
                this.loading = false;
            }
        },
        
        filterTiendas() {
            if (this.searchTerm.length === 0) {
                this.filteredTiendas = [];
                this.hideDropdown();
                return;
            }
            
            const term = this.searchTerm.toLowerCase();
            this.filteredTiendas = this.allTiendas.filter(tienda => 
                tienda.toLowerCase().includes(term)
            ).sort((a, b) => {
                // Priorizar coincidencias que empiecen con el término
                const aStarts = a.toLowerCase().startsWith(term);
                const bStarts = b.toLowerCase().startsWith(term);
                if (aStarts && !bStarts) return -1;
                if (!aStarts && bStarts) return 1;
                return a.localeCompare(b);
            });
            
            this.selectedIndex = -1;
            this.showDropdown = this.filteredTiendas.length > 0;
        },
        
        selectTienda(tienda) {
            this.searchTerm = tienda;
            this.hideDropdown();
            
            // Actualizar el input hidden del formulario
            const input = document.getElementById('tienda');
            if (input) {
                input.value = tienda;
            }
        },
        
        navigateDown() {
            if (this.selectedIndex < this.filteredTiendas.slice(0, 10).length - 1) {
                this.selectedIndex++;
            }
        },
        
        navigateUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },
        
        selectCurrent() {
            if (this.selectedIndex >= 0 && this.selectedIndex < this.filteredTiendas.length) {
                this.selectTienda(this.filteredTiendas[this.selectedIndex]);
            }
        },
        
        hideDropdown() {
            this.showDropdown = false;
            this.selectedIndex = -1;
        },
        
        highlightMatch(text, term) {
            if (!term) return text;
            
            const regex = new RegExp(`(${term})`, 'gi');
            return text.replace(regex, '<mark class="bg-yellow-200 font-semibold">$1</mark>');
        }
    }
}
</script>