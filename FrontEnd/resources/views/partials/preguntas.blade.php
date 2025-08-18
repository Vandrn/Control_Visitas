<div id="seccion-{{ $seccion }}" style="display: none;">
    <div class="subtema-wrapper">
        <label class="subtema">{{ $titulo }}</label>
    </div>

    <br><br><br>
    @php
    // 🔵 Preguntas con imagen por sección según marcadas en azul
    $preguntasConImagen = [
    2 => [1, 2, 3, 4, 5, 6, 7, 8, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 21, 22], // Operaciones
    4 => [1, 2, 5, 6, 7, 8, 9], // Producto
    5 => [1, 9] // Personal
    ];
    @endphp

    @php
    //Preguntas no aplicables por sección
    $preguntasNoAplica = [
    4 => [7, 8], // Producto
    5 => [6, 7], // Personal
    ];
    @endphp

    @foreach ($preguntas as $index => $pregunta)
    @php
    $idPregunta = 'preg_' . str_pad($seccion, 2, '0', STR_PAD_LEFT) . '_' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
    $nombreObservacion = 'obs_' . str_pad($seccion, 2, '0', STR_PAD_LEFT) . '_01';
    $nombreImagen = 'IMG_OBS_' . strtoupper(substr($titulo, 0, 3)) ; // IMG_OBS_OPR, IMG_OBS_ADM, etc.
    $imagenesGuardadas = json_decode($resultado->$nombreImagen ?? '[]', true); // Obtener imágenes guardadas
    $nombreImagenIndividual = 'IMG_' . str_pad($seccion, 2, '0', STR_PAD_LEFT) . '_' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
    @endphp

    <label class="pregunta" id="label_{{ $idPregunta }}">{{ $index + 1 }}. {{ $pregunta }}</label>
    <br>

    @if (strpos($pregunta, 'Observaciones del área') !== false)
    <!-- Campo de observación -->
    <div class="observaciones">
        <textarea class="texto" id="{{ $idPregunta }}" name="{{ $nombreObservacion }}" placeholder="Escriba sus observaciones aquí..." rows="4" cols="50" required></textarea>
        <br>
        <div class="file_container">
            <input type="file" id="imagen_{{ $idPregunta }}" name="{{ $nombreImagen }}[]" class="form-control-file" accept="image/png, image/jpeg" multiple>
            <br>
        </div>
    </div>
    <!-- Mostrar imágenes guardadas -->
    @if (!empty($imagenesGuardadas))
    <div class="imagenes-guardadas">
        @foreach ($imagenesGuardadas as $imagen)
        <div class="imagen-container">
            <img src="{{ asset($imagen) }}" alt="Imagen Observación" width="150">
        </div>
        @endforeach
    </div>
    @endif
    @else
    <!-- PREGUNTA CON RADIO Y POSIBLE IMAGEN -->
    <div class="radio-buttons">
        @for ($i = 1; $i <= 5; $i++)
            <input type="radio" id="{{ $idPregunta }}_{{ $i }}" name="{{ $idPregunta }}" value="{{ $i }}" required>
            <label for="{{ $idPregunta }}_{{ $i }}">{{ $i }}</label>
        @endfor
        @if (isset($preguntasNoAplica[(int)$seccion]) && in_array($index + 1, $preguntasNoAplica[(int)$seccion]))
            <input type="radio" id="{{ $idPregunta }}_NA" name="{{ $idPregunta }}" value="NA">
            <label for="{{ $idPregunta }}_NA">No aplica</label>
        @endif
    </div>

    <!--Imagen opcional para ciertas preguntas marcadas en azul-->
    @if (isset($preguntasConImagen[(int)$seccion]) && in_array($index + 1, $preguntasConImagen[(int)$seccion]))
        <div class="input-imagen">
            <label for="{{ $nombreImagenIndividual }}" class="form-label">
                Subir hasta 5 imágenes (opcional)
            </label>
            <input
                type="file"
                name="{{ $nombreImagenIndividual }}[]"
                id="{{ $nombreImagenIndividual }}"
                multiple
                accept="image/*"
            >
        </div>
    @endif
    @endif
    <br>
    @endforeach

    <button type="button" class="boton btnSiguiente">Continuar</button>
</div>