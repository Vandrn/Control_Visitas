@extends('layouts.app')

@section('content')

    @include('partials.header')

    <div class="contenedor-centrado" style="margin-top: 40px;">
        @include('partials.seccion-1')
    </div>

    {{-- Botón de navegación a la siguiente etapa --}}
    <div style="text-align:center; margin-top:40px;">
        <a href="{{ route('visita.operaciones.intro') }}" class="boton">
            Continuar
        </a>
    </div>

@endsection
