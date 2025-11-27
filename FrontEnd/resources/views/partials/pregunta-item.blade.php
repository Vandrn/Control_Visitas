@php
    // detecta si es pregunta de observación
    $esObs = str_contains($pregunta, 'Observaciones del área');

    // para IDs
    $idPregunta = 'preg_' . str_pad($seccion,2,'0',STR_PAD_LEFT) . '_' . str_pad($index+1,2,'0',STR_PAD_LEFT);

    // nombres de campo
    $campoObs = "obs_" . str_pad($seccion,2,'0',STR_PAD_LEFT) . "_01";
    $nombreImagenGeneral = "IMG_OBS_" . strtoupper(substr($titulo,0,3));
    $nombreImagenIndividual = "IMG_" . str_pad($seccion,2,'0',STR_PAD_LEFT) . "_" . str_pad($index+1,2,'0',STR_PAD_LEFT);

    // reglas especiales
    $preguntasConImagen = $preguntasConImagen ?? [];
    $preguntasNoAplica = $preguntasNoAplica ?? [];

@endphp

<label class="pregunta">{{ $index+1 }}. {{ $pregunta }}</label>

@if ($esObs)

    <div class="observaciones">
        <textarea name="{{ $campoObs }}" class="texto" required placeholder="Escriba sus observaciones aquí..."></textarea>

        <input type="file"
               name="{{ $nombreImagenGeneral }}[]"
               multiple
               accept="image/*"
               class="form-control-file">
    </div>

@else

    <div class="radio-buttons">
        @for ($i=1; $i<=5; $i++)
            <input type="radio" id="{{ $idPregunta }}_{{ $i }}" name="{{ $idPregunta }}" value="{{ $i }}" required>
            <label for="{{ $idPregunta }}_{{ $i }}">{{ $i }}</label>
        @endfor

        {{-- No Aplica --}}
        @if (in_array($index+1, $preguntasNoAplica))
            <input type="radio" id="{{ $idPregunta }}_NA" name="{{ $idPregunta }}" value="NA">
            <label for="{{ $idPregunta }}_NA">No aplica</label>
        @endif
    </div>

    {{-- Si esta pregunta permite subir imágenes --}}
    @if (in_array($index+1, $preguntasConImagen))
        <div class="input-imagen">
            <label>Subir hasta 5 imágenes (opcional)</label>
            <input type="file"
                   name="{{ $nombreImagenIndividual }}[]"
                   multiple
                   accept="image/*">
        </div>
    @endif

@endif
