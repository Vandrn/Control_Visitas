@extends('layouts.app')

@section('content')
    @include('partials.header')

    <div class="contenedor-centrado" style="margin-top: 40px;">
        @include('partials.datos')

        <a href="{{ route('visita.operaciones.intro') }}" class="boton" style="margin-top: 40px;">
            Continuar
        </a>
    </div>
@endsection
