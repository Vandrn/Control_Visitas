<!-- Séptima Parte: Plan de Acción -->
<div id="seccion-7" style="display: none;">
    <div class="subtema-wrapper">
        <label class="subtema">Plan de Acción</label>
    </div>
    <div class="plan-container">
        @php
        $numPregunta = 1;
        @endphp
        @for ($i = 1; $i <= 2; $i++)
            <div class="plan-item">
                <div class="question-container">
                    <label class="pregunta">
                        {{ $numPregunta }}. Plan de Acción n°{{ $i }}{{ $i == 2 ? ' (opcional)' : '' }}
                    </label>
                </div>
                <div class="date-container">
                    <input
                        type="text"
                        name="PLAN_0{{ $i }}"
                        placeholder="Describe el Plan de Acción n°{{ $i }}"
                        {{ $i == 1 ? 'required' : '' }}
                        class="{{ $i == 2 ? 'opcional' : '' }}"
                    >
                </div>
            </div>
            @php $numPregunta++; @endphp
            <div class="plan-item">
                <div class="question-container">
                    <label class="fechas">
                        {{ $numPregunta }}. Fecha de cumplimiento meta para el plan n°{{ $i }}{{ $i == 2 ? ' (opcional)' : '' }}
                    </label>
                </div>
                <div class="date-container">
                    <input
                        type="date"
                        name="FECHA_PLAN_0{{ $i }}"
                        {{ $i == 1 ? 'required' : '' }}
                        class="{{ $i == 2 ? 'opcional' : '' }}"
                    >
                </div>
            </div>
            @php $numPregunta++; @endphp
        @endfor
    </div>

    <!-- Plan Opcional Adicional -->
    <div class="plan-container">
        <div class="plan-item">
            <div class="question-container">
                <label class="pregunta">{{ $numPregunta }}. Si existe algún punto adicional, por favor detallarlo acá (opcional)</label>
            </div>
            <div class="date-container">
                <input type="text" name="PLAN_ADIC" placeholder="Plan de Acción opcional" class="opcional">
            </div>
        </div>
        @php $numPregunta++; @endphp
        <div class="plan-item">
            <div class="question-container">
                <label class="fechas">{{ $numPregunta }}. Fecha de cumplimiento meta para el punto adicional (opcional)</label>
            </div>
            <div class="date-container">
                <input type="date" name="FECHA_PLAN_ADIC" class="opcional">
            </div>
        </div>
    </div>

    <button type="submit" id="btnSiguiente" class="boton btnSiguiente">Enviar</button>
</div>
