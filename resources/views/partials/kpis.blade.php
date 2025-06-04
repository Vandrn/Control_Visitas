<!-- Sexta Parte: KPIs -->
<div id="seccion-6" style="display: none;">
    <label class="subtema" id="kpi">KPI</label>

    <div class="kpi-container">
        <div class="kpi-columna izquierda">
            @php
            $kpis_izquierda = ['Venta', 'Margen', 'Conversion'];
            @endphp

            @foreach ($kpis_izquierda as $kpi)
            <div class="kpi-item">
                <label class="pregunta">{{ $loop->iteration }}. {{ $kpi }}</label>
                <div class="kpi-opciones">
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration }}" value="Cumple" required> Cumple</label>
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration }}" value="No Cumple"> No Cumple</label>
                </div>
                <div class="kpi-variacion">
                    <label>Variación vs meta (% o valor):</label>
                    <input type="number" name="var_06_0{{ $loop->iteration }}" step="any" placeholder="Ej: -2.5 o 3.8" required>
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
                <div class="kpi-opciones">
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration + 3 }}" value="Cumple" required> Cumple</label>
                    <label><input type="radio" name="preg_06_0{{ $loop->iteration + 3 }}" value="No Cumple"> No Cumple</label>
                </div>
                <div class="kpi-variacion">
                    <label>Variación vs meta (% o valor):</label>
                    <input type="number" name="var_06_0{{ $loop->iteration + 3 }}" step="any" placeholder="Ej: -2.5 o 3.8" required>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    
    <div class="observaciones">
        <label id="observacion" class="pregunta">7. Observaciones del área KPIs</label>
        <br>
        <textarea class="texto" placeholder="Escriba sus observaciones aquí..." rows="4" cols="50" name="obs_06_01" required></textarea>
        <div class="file_container">
            <input type="file" id="IMG_OBS_KPI" name="IMG_OBS_KPI" class="form-control-file" accept="image/png, image/jpeg" required>
        </div>
    </div>

    <button type="button" class="boton btnSiguiente">Continuar</button>
</div>