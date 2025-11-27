@extends('layouts.app')

@section('content')
    @include('partials.header')

    <div class="introduccion" style="margin-top: 60px;">
        <h2 class="titulo-intro">Bienvenido al Control de Visitas a Tiendas</h2>

        <p>
            Este formulario evalúa diferentes áreas de la tienda.
            Haz clic en "Empezar" para continuar.
        </p>

        <a href="{{ route('visita.datos') }}" class="boton" style="margin-top: 40px;">
            Empezar
        </a>
    </div>
@endsection
