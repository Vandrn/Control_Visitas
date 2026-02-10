<!-- Sexta Parte: KPIs -->
<div id="seccion-6" style="display: none;">
    <div class="subtema-wrapper">
        <label class="subtema">KPI</label>
    </div>
    <div class="kpi-container">
        <div class="kpi-columna izquierda">
            @php
            $kpis_izquierda = ['Venta', 'Margen', 'Conversion'];
            @endphp

            @foreach ($kpis_izquierda as $kpi)
            <div class="kpi-item">
                <label class="pregunta">{{ $loop->iteration }}. {{ $kpi }}</label>
                <div class="kpi-variacion">
                    <br>
                    <label>Variación vs meta (% o valor):</label><br>
                    <input type="number" name="var_06_0{{ $loop->iteration }}" step="any" placeholder="Ej: -2.5 o 3.8" required>
                    <br><br>
                </div>
                <div class="kpi-opciones">
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration }}" value="Cumple" required> Cumple</label>
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration }}" value="No Cumple"> No Cumple</label>
                </div>
            </div>
            @endforeach
        </div>
        <div class="kpi-columna derecha">
            @php
            $kpis_derecha = ['UPT', 'DPT', 'NPS'];
            @endphp

            @foreach ($kpis_derecha as $kpi)
            <div class="kpi-item">
                <label class="pregunta">{{ $loop->iteration + 3 }}. {{ $kpi }}</label>
                <div class="kpi-variacion">
                    <br>
                    <label>Variación vs meta (% o valor):</label><br>
                    <input type="number" name="var_06_0{{ $loop->iteration + 3 }}" step="any" placeholder="Ej: -2.5 o 3.8" required>
                    <br><br>
                </div>
                <div class="kpi-opciones">
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration + 3 }}" value="Cumple" required> Cumple</label>
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration + 3 }}" value="No Cumple"> No Cumple</label>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="observaciones">
        <label id="observacion" class="pregunta">7. Observaciones del Área KPIs</label>
        <br>
        <textarea class="texto" placeholder="Escriba sus observaciones aquí..." rows="4" cols="50" name="obs_06_01" required></textarea>
    </div>

    <button type="button" class="boton btnSiguiente">Continuar</button>
</div>