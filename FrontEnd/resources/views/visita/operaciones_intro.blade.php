@extends('layouts.app')

@section('content')

@include('partials.header')

<div class="introduccion" style="margin-top: 60px; text-align:center;">
    <h2 class="titulo-intro">Evaluación del Área de Operaciones</h2>

    <p>
        Evalúe los siguientes elementos en la tienda del 1 al 5,
        siendo 1 el peor y 5 el mejor.
    </p>

    <a href="{{ route('visita.operaciones.preguntas') }}" class="boton" style="margin-top:50px;">
        Empezar
    </a>
</div>

@endsection
