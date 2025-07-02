<div x-data="filtrosComponent()" class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900">Filtros de Búsqueda</h3>
        <button @click="toggleFiltros()" 
                class="md:hidden bg-blue-600 text-white px-3 py-2 rounded-md text-sm">
            <span x-text="mostrarFiltros ? 'Ocultar' : 'Mostrar'"></span> Filtros
        </button>
    </div>

    <form method="GET" action="{{ route('admin.dashboard') }}" 
          x-show="mostrarFiltros" 
          x-transition:enter="transition ease-out duration-200"
          x-transition:enter-start="opacity-0 transform scale-95"
          x-transition:enter-end="opacity-100 transform scale-100"
          class="space-y-4 md:space-y-0">

        <!-- Primera fila: Fechas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Fecha Inicio -->
            <div>
                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Inicio
                </label>
                <input type="date" 
                       id="fecha_inicio" 
                       name="fecha_inicio" 
                       value="{{ $filtros['fecha_inicio'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>

            <!-- Fecha Fin -->
            <div>
                <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Fin
                </label>
                <input type="date" 
                       id="fecha_fin" 
                       name="fecha_fin" 
                       value="{{ $filtros['fecha_fin'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
        </div>

        <!-- Segunda fila: País y Evaluador -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- País -->
            <div>
                <label for="pais" class="block text-sm font-medium text-gray-700 mb-1">
                    País
                    @if(session('admin_user.rol') === 'evaluador_pais' && session('admin_user.pais_acceso') !== 'ALL')
                        <span class="text-xs text-orange-600 font-normal">(Acceso restringido)</span>
                    @endif
                </label>
                <select id="pais" 
                        name="pais" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        @if(session('admin_user.rol') === 'evaluador_pais' && session('admin_user.pais_acceso') !== 'ALL') disabled @endif>
                    
                    @if(session('admin_user.rol') === 'evaluador_pais' && session('admin_user.pais_acceso') !== 'ALL')
                        <!-- Usuario con restricción de país -->
                        <option value="{{ session('admin_user.pais_acceso') }}" selected>
                            {{ session('admin_user.pais_acceso') }} (Asignado)
                        </option>
                    @else
                        <!-- Usuario sin restricción -->
                        <option value="">Todos los países</option>
                        @foreach($paises as $pais)
                            <option value="{{ $pais }}" 
                                    {{ ($filtros['pais'] ?? '') === $pais ? 'selected' : '' }}>
                                {{ $pais }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Evaluador -->
            <div>
                <label for="evaluador" class="block text-sm font-medium text-gray-700 mb-1">
                    Evaluador
                </label>
                <select id="evaluador" 
                        name="evaluador" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Todos los evaluadores</option>
                    @foreach($evaluadores as $evaluador)
                        <option value="{{ $evaluador['email'] }}" 
                                {{ ($filtros['evaluador'] ?? '') === $evaluador['email'] ? 'selected' : '' }}>
                            {{ $evaluador['nombre'] }} ({{ $evaluador['email'] }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Tercera fila: Tienda con Autocomplete -->
        @include('admin.components.tienda-autocomplete')

        <!-- Botones de acción -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200">
            <button type="submit" 
                    class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Aplicar Filtros
            </button>

            <a href="{{ route('admin.dashboard') }}" 
               class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Limpiar Filtros
            </a>

            <button type="button" 
                    @click="aplicarFiltrosRapidos('hoy')"
                    class="inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Hoy
            </button>

            <button type="button" 
                    @click="aplicarFiltrosRapidos('semana')"
                    class="inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Esta Semana
            </button>

            <button type="button" 
                    @click="aplicarFiltrosRapidos('mes')"
                    class="inline-flex justify-center items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                Este Mes
            </button>
        </div>

        <!-- Indicadores de filtros activos -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <div class="flex flex-wrap gap-2">
                @if(!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        📅 
                        @if(!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin']))
                            {{ $filtros['fecha_inicio'] }} → {{ $filtros['fecha_fin'] }}
                        @elseif(!empty($filtros['fecha_inicio']))
                            Desde: {{ $filtros['fecha_inicio'] }}
                        @else
                            Hasta: {{ $filtros['fecha_fin'] }}
                        @endif
                    </span>
                @endif

                @if(!empty($filtros['pais']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        🌍 {{ $filtros['pais'] }}
                    </span>
                @endif

                @if(!empty($filtros['tienda']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        🏪 {{ $filtros['tienda'] }}
                    </span>
                @endif

                @if(!empty($filtros['evaluador']))
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        👤 {{ $filtros['evaluador'] }}
                    </span>
                @endif
            </div>
        </div>
    </form>
</div>

<script>
function filtrosComponent() {
    return {
        mostrarFiltros: window.innerWidth >= 768, // Mostrar en desktop, ocultar en móvil

        toggleFiltros() {
            this.mostrarFiltros = !this.mostrarFiltros;
        },

        aplicarFiltrosRapidos(periodo) {
            const hoy = new Date();
            let fechaInicio, fechaFin;

            switch(periodo) {
                case 'hoy':
                    fechaInicio = fechaFin = hoy.toISOString().split('T')[0];
                    break;
                case 'semana':
                    const inicioSemana = new Date(hoy);
                    inicioSemana.setDate(hoy.getDate() - hoy.getDay());
                    fechaInicio = inicioSemana.toISOString().split('T')[0];
                    fechaFin = hoy.toISOString().split('T')[0];
                    break;
                case 'mes':
                    fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
                    fechaFin = hoy.toISOString().split('T')[0];
                    break;
            }

            // SOLUCIÓN: En lugar de form.submit(), construir URL GET manualmente
            this.aplicarFiltrosConURL(fechaInicio, fechaFin);
        },

        aplicarFiltrosConURL(fechaInicio, fechaFin) {
            // Obtener valores actuales del formulario
            const pais = document.getElementById('pais').value;
            const evaluador = document.getElementById('evaluador').value;
            const tienda = document.getElementById('tienda').value;
            
            // Construir URL con parámetros GET
            const baseUrl = '{{ route("admin.dashboard") }}';
            const params = new URLSearchParams();
            
            // Agregar parámetros solo si tienen valor
            if (fechaInicio) params.append('fecha_inicio', fechaInicio);
            if (fechaFin) params.append('fecha_fin', fechaFin);
            if (pais) params.append('pais', pais);
            if (evaluador) params.append('evaluador', evaluador);
            if (tienda) params.append('tienda', tienda);
            
            // Construir URL final
            const finalUrl = baseUrl + (params.toString() ? '?' + params.toString() : '');
            
            // Redirigir usando GET (sin CSRF token)
            window.location.href = finalUrl;
        }
    }
}
</script>