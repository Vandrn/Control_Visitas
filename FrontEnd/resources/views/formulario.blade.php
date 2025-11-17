<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Control de Visitas</title>
    <link rel="stylesheet" href="{{ asset('css/formulario1.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/formStorage.js') }}"></script>
    <script type="module" src="{{ asset('js/formulario1.js') }}"></script>

    <!-- Evitar parpadeo: ocultar las secciones del formulario hasta que JS restaure la visible -->
    <!-- Nota: el bloqueo inicial fue movido a la lógica de servidor/cookie más abajo para evitar conflictos con JS -->

    {{-- Mostrar en el servidor la sección guardada si existe cookie cv_form_idx (evita flicker). Si no existe, ocultar todo y dejar que JS lo restaure. --}}
    @php
        $secciones = ["intro","datos","seccion-1","intro-2","seccion-2","intro-3","seccion-3","intro-4","seccion-4","intro-5","seccion-5","seccion-6","seccion-7"];
        $idx = request()->cookie('cv_form_idx');
        $idToShow = null;
        if ($idx !== null && is_numeric($idx)) {
            $i = intval($idx);
            if ($i >=0 && $i < count($secciones)) $idToShow = $secciones[$i];
        }
    @endphp
    <style>
        @if($idToShow)
            /* Ocultar todo pero mostrar solo la sección guardada */
            .formulario form > * { display: none !important; }
            #{{ $idToShow }} { display: block !important; }
        @else
            /* Fallback: ocultar todo pero sin !important para permitir que JS muestre la sección cuando el usuario haga "Empezar" */
            .formulario form > * { display: none; }
        @endif
    </style>

    <!-- 📍 ESTILOS ADICIONALES PARA VALIDACIÓN DE DISTANCIA -->
    <style>
        /* Ocultar solo al inicio para evitar flicker,
            pero permitir que JS controle visibilidad */
        [id^="intro-"],
        [id^="preguntas-"],
        [id^="seccion-"],
        #datos {
            display: none;
        }

        /* Cuando JS active la clase visible-forzada, muéstrala */
        .visible-forzada {
            display: block !important;
        }
        
        .distance-validation {
            margin-top: 8px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.3s ease;
        }

        .distance-success {
            background: #10b981;
            color: white;
            border-left: 4px solid #059669;
        }

        .distance-danger {
            background: #ef4444;
            color: white;
            border-left: 4px solid #dc2626;
        }

        .distance-warning {
            background: #f59e0b;
            color: white;
            border-left: 4px solid #d97706;
        }

        .distance-info {
            background: #3b82f6;
            color: white;
            border-left: 4px solid #2563eb;
        }

        .input-error {
            border: 2px solid red;
            background-color: #ffe6e6;
        }


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 📱 RESPONSIVE PARA MÓVILES */
        @media (max-width: 768px) {
            .distance-validation {
                font-size: 12px;
                padding: 10px 12px;
            }
        }
    </style>
</head>

<body>
    @include ('partials.header')
    <div class="formulario">
        <form onsubmit="return false;" method="POST" enctype="multipart/form-data">
            @csrf
            @include('partials.intro')
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
    <div style="height: 80px;"></div>
</body>
<footer class="footer-banner">
    <img src="{{ asset('images/banner.png') }}" alt="logos" id="logos" class="logo-banner">
</footer>

</html>