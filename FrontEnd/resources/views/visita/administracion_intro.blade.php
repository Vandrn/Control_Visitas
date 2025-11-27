@extends('layouts.app')

@section('content')
@include('partials.header')

<div class="introduccion" style="margin-top:60px;">
    <h2 class="titulo-intro">Evaluación del Área de Administración</h2>

    <p>Evalúe los siguientes elementos del 1 al 5, siendo 1 el peor y 5 el mejor.</p>

    <a href="{{ route('visita.administracion.preguntas') }}" class="boton" style="margin-top:40px;">
        Empezar
    </a>
</div>
@endsection