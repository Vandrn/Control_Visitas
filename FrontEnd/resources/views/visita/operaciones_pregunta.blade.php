@extends('layouts.app')

@section('content')

@include('partials.header')

<div class="subtema-wrapper" style="margin-top:30px;">
    <label class="subtema">Operaciones</label>
</div>

<div style="margin-top:40px;">

    @php
        $seccion = 2;
        $titulo = "Operaciones";
    @endphp

    @foreach ($preguntasOperaciones as $index => $pregunta)
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
    <a href="{{ route('visita.administracion.intro') }}" class="boton">
        Continuar
    </a>
</div>

@endsection

