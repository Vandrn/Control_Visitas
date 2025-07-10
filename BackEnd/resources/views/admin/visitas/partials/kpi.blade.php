@if(!empty($kpis))
<div class="p-6 bg-white rounded-lg shadow mt-4 mb-6">
    <h2 class="text-lg font-semibold mb-4 text-gray-800 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 11a1 1 0 011-1h3a1 1 0 010 2H3a1 1 0 01-1-1zm6-5a1 1 0 011-1h9a1 1 0 010 2H9a1 1 0 01-1-1zm-4 10a1 1 0 100 2h13a1 1 0 100-2H4zm0-5a1 1 0 100 2h13a1 1 0 100-2H4z" />
        </svg>
        Indicadores KPI
    </h2>

    {{-- Contenedor de tarjetas KPI --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        @foreach($kpis as $kpi)
        @if(str_starts_with($kpi['codigo_pregunta'], 'PREG_05_'))
        <div class="border rounded-xl p-4 shadow-sm bg-gray-50">
            <h6 class="text-base font-semibold text-gray-800 mb-2">
                {{ $kpis_nombres[$kpi['codigo_pregunta']] ?? $kpi['codigo_pregunta'] }}
            </h6>
            <p class="text-sm text-gray-700">
                <span class="font-medium">Estado:</span>
                <span class="{{ $kpi['valor'] === 'Cumple' ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold' }}">
                    {{ $kpi['valor'] }}
                </span>
            </p>
            @php
            $var = $kpi['variacion'] ?? 0;
            @endphp
            <p class="text-sm text-gray-700">
                <span class="font-medium">Variación:</span>
                <span class="{{ $var < 0 ? 'text-red-600' : 'text-blue-600' }}">
                    {{ $var }}
                </span>
            </p>
        </div>
        @endif
        @endforeach
    </div>

    {{-- Observación KPI --}}
    @php
    $obs = collect($kpis)->firstWhere('codigo_pregunta', 'OBS_KPI');
    @endphp

    <div class="mt-6">
        <h6 class="text-sm font-medium text-gray-800 mb-1">Observaciones</h6>
        <div class="border rounded-md p-3 bg-gray-100 text-sm text-gray-800">
            {{ $obs['valor'] ?? 'Sin observaciones' }}
        </div>
    </div>
</div>
@endif