<div id="seccion-{{ $seccion }}" style="display: none;">
    <label class="subtema">{{ $titulo }}</label>
    <br><br><br>

    @foreach ($preguntas as $index => $pregunta)
        @php
            $idPregunta = 'preg_' . str_pad($seccion, 2, '0', STR_PAD_LEFT) . '_' . str_pad($index + 1, 2, '0', STR_PAD_LEFT);
            $nombreObservacion = 'obs_' . str_pad($seccion, 2, '0', STR_PAD_LEFT) . '_01';
            $nombreImagen = 'img_obs_' . strtoupper(substr($titulo, 0, 3)) . '_url'; // IMG_OBS_OPR, IMG_OBS_ADM, etc.
            $imagenesGuardadas = json_decode($resultado->$nombreImagen ?? '[]', true); // Obtener imágenes guardadas
        @endphp

        <label class="pregunta" id="label_{{ $idPregunta }}">{{ $index + 1 }}. {{ $pregunta }}</label>
        <br>

        @if (strpos($pregunta, 'Observaciones del área') !== false)
            <!-- Campo de observación -->
            <div class="observaciones">
                <textarea class="texto" id="{{ $idPregunta }}" name="{{ $nombreObservacion }}" placeholder="Escriba sus observaciones aquí..." rows="4" cols="50" required></textarea>
                <br>
                <div class="file_container">
                    <!-- Input para subir múltiples imágenes -->
                    <input type="file" id="imagen_{{ $idPregunta }}" name="{{ $nombreImagen }}" class="form-control-file" accept="image/png, image/jpeg">
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
            <!-- Radio buttons para las preguntas -->
            <div class="radio-buttons">
                @for ($i = 1; $i <= 5; $i++)
                    <input type="radio" id="{{ $idPregunta }}_{{ $i }}" name="{{ $idPregunta }}" value="{{ $i }}" required>
                    <label for="{{ $idPregunta }}_{{ $i }}">{{ $i }}</label>
                @endfor
            </div>
        @endif
        <br>
    @endforeach

    <button type="button" class="boton btnSiguiente">Continuar</button>
</div>
