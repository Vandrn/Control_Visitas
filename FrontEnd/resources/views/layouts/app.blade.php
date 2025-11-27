<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Control de Visitas</title>

    <link rel="stylesheet" href="{{ asset('css/formulario1.css') }}">
    <link rel="stylesheet" href="{{ asset('css/formulario-styles.css') }}">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="module" src="{{ asset('js/app.js') }}"></script>

    @stack('styles')
</head>

<body>
    @yield('content')

    <footer class="footer-banner">
        <img src="{{ asset('images/banner.png') }}" alt="logos" id="logos" class="logo-banner">
    </footer>
</body>

</html>
