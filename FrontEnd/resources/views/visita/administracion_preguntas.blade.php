@extends('layouts.app')

@section('content')

@include('partials.header')

<div class="subtema-wrapper">
    <label class="subtema">Administración</label>
</div>

<div style="margin-top:40px;">

    @php
        $seccion = 3;
        $titulo = "Administración";

        // misma estructura que operaciones
        $preguntasConImagen = [];  // administración NO usa imágenes opcionales
        $preguntasNoAplica = [];   // administración NO tiene NA
    @endphp

    @foreach ($preguntasAdministracion as $index => $pregunta)
        @include('partials.pregunta-item', [
            'seccion' => $seccion,
            'titulo' => $titulo,
            'pregunta' => $pregunta,
            'index' => $index,
            'preguntasConImagen' => $preguntasConImagen,
            'preguntasNoAplica' => $preguntasNoAplica
        ])
    @endforeach

</div>

<div style="text-align:center; margin-top:40px;">
    <a href="{{ route('visita.producto.intro') }}" class="boton">
        Continuar
    </a>
</div>

@endsection
