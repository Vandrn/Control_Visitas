<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Control de Visitas</title>
    <link rel="stylesheet" href="{{ asset('css/formulario1.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/formulario1.js') }}" defer></script>
</head>

<body>
    @include ('partials.header')


    @include('partials.intro')
    <div class="formulario">

        <form action="formulario" method="POST" enctype="multipart/form-data">
            @csrf
            <br>
            <br>
            <br>
            <br>
            <br>
            @include('partials.datos')

            @include ('partials.seccion-1')
            @include ('partials.largas')

            @include('partials.kpis')

            @include('partials.seccion-7')

            @include('partials.final')

            <input type="hidden" id="ubicacion" name="ubicacion">
            <input type="hidden" name="FECHA_HORA_INICIO" id="fecha_inicio">

        </form>
    </div>
    @include ('partials.footer')
</body>

</html>