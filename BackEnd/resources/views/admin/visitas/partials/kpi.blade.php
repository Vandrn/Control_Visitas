@if(!empty($kpis))
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Indicadores KPI</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Indicador</th>
                        <th>Estado</th>
                        <th>Variación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kpis as $kpi)
                        @if(str_starts_with($kpi['codigo_pregunta'], 'PREG_05_'))
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $kpis_nombres[$kpi['codigo_pregunta']] ?? $kpi['codigo_pregunta'] }}</td>
                            <td>
                                <span class="badge {{ $kpi['valor'] === 'Cumple' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $kpi['valor'] }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $var = $kpi['variacion'] ?? 0;
                                @endphp
                                <span class="{{ $var < 0 ? 'text-danger' : 'text-primary' }}">
                                    {{ $var }}
                                </span>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Observación KPI --}}
        @php
            $obs = collect($kpis)->firstWhere('codigo_pregunta', 'OBS_KPI');
        @endphp

        <div class="mt-3">
            <h6>Observaciones</h6>
            <div class="border rounded p-3 bg-light">
                {{ $obs['valor'] ?? 'Sin observaciones' }}
            </div>
        </div>
    </div>
</div>
@endif
