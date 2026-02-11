<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultado de la visita</title>
    <style>
        h3.seccion-titulo {
            background-color: #FFC112;
            text-align: center;
            padding: 10px;
            font-size: 18px;
            border-radius: 8px;
        }

        p.pregunta {
            font-size: 11px;
            margin-bottom: 6px;
        }

        hr {
            border: 1px solid #bbb;
            margin: 20px 0;
        }
    </style>

</head>

<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">
    @php use Illuminate\Support\Str; @endphp
    @php
        $resumenAreas = $datos['resumen_areas'] ?? [];

        // Si viene como string JSON
        if (is_string($resumenAreas)) {
            $resumenAreas = json_decode($resumenAreas, true) ?: [];
        }

        // Si viene como objeto (stdClass)
        if (is_object($resumenAreas)) {
            $resumenAreas = json_decode(json_encode($resumenAreas), true) ?: [];
        }

        // Si viene null o cualquier cosa rara
        if (!is_array($resumenAreas)) {
            $resumenAreas = [];
        }
    @endphp
    <!--
    correo_realizo: {{ $datos['correo_realizo'] ?? '' }}
    correo_tienda: {{ $datos['correo_tienda'] ?? '' }}
    correo_jefe_zona: {{ $datos['correo_jefe_zona'] ?? '' }}
    -->

    <h1>📝 Resultado de la visita a {{ Str::before($datos['tienda'], ' -') }}</h1>

    <p>
        <strong>Fecha:</strong> {{ $datos['fecha_hora_fin'] }}<br>
        <strong>Zona:</strong> {{ $datos['zona'] }}<br>
        <strong>País:</strong> {{ $datos['pais'] }}<br>
        <strong>Puntaje Total:</strong> {{ $datos['puntos_totales'] ?? 'N/A' }}
        (equivale a {{ $datos['estrellas'] ?? 'N/A' }} estrellas)
    </p>

    <hr>

    <h2>📊 Resultado por área</h2>
    <ul>
        @foreach($resumenAreas as $area)
        <li><strong>{{ $area['nombre'] }}:</strong> {{ $area['puntos'] }} puntos (equivalente a {{ $area['estrellas'] }} estrellas)</li>
        @endforeach
    </ul>

    <hr>

    <h2>✅ Resultado detallado por pregunta</h2>
    @foreach($datos['secciones'] ?? [] as $seccion)
    <h3 class="seccion-titulo">
        Área {{ ucfirst($seccion['nombre_seccion']) }}
    </h3>



    @foreach($seccion['preguntas'] as $preg)
    @php
    $esObservacion = \Illuminate\Support\Str::startsWith($preg['codigo_pregunta'], 'OBS_');
    $nombre = \App\Helpers\PreguntaHelper::nombreBonito($preg['codigo_pregunta']);
    @endphp

    @if ($esObservacion)
    <p><strong>Observaciones:</strong><br>{{ $preg['respuesta'] ?? 'Sin observaciones' }}</p>

    @if (!empty($preg['imagenes']))
    @foreach ($preg['imagenes'] as $img)
    @php
    $urlPublica = str_replace('https://storage.cloud.google.com/', 'https://storage.googleapis.com/', $img);
    @endphp
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="{{ $urlPublica }}" alt="Imagen observación" style="max-width: 100%; border: 1px solid #ccc; margin-top: 5px;">
    </div>
    @endforeach
    @endif
    @else
    <p class="pregunta">
        <strong>{{ $nombre }}:</strong>
        @php
        $valor = is_numeric($preg['respuesta']) ? intval($preg['respuesta']) : null;
        @endphp
        @if (!is_null($valor) && $valor > 0)
        {{ str_repeat('★', $valor) }}
        @else
        {{ $preg['respuesta'] ?? 'N/A' }}
        @endif
    </p>

    @if (!empty($preg['imagenes']))
    @foreach ($preg['imagenes'] as $img)
    @php
    $urlPublica = str_replace('https://storage.cloud.google.com/', 'https://storage.googleapis.com/', $img);
    @endphp
    <div style="text-align: center; margin-bottom: 10px;">
        <img src="{{ $urlPublica }}" alt="Imagen respuesta" style="max-width: 100%; border: 1px solid #ccc; margin-top: 5px;">
    </div>
    @endforeach
    @endif
    @endif
    @endforeach

    <hr>
    @endforeach

    <h2>🧮 KPIs</h2>
    <ul>
        @foreach($datos['kpis'] ?? [] as $kpi)
        @php
        $esObservacion = \Illuminate\Support\Str::startsWith(strtoupper($kpi['codigo_pregunta'] ?? ''), 'OBS_');
        $nombre = \App\Helpers\PreguntaHelper::nombreBonito($kpi['codigo_pregunta']);
        @endphp

        @if (!$esObservacion)
        <li><strong>{{ $nombre }}:</strong> {{ $kpi['valor'] }} (variación: {{ $kpi['variacion'] ?? '0' }})</li>
        @endif
        @endforeach
    </ul>

    @if(collect($datos['kpis'] ?? [])->contains(fn($k) => \Illuminate\Support\Str::startsWith(strtoupper($k['codigo_pregunta'] ?? ''), 'OBS_')))
    <h3>Observaciones:</h3>
    <ul>
        @foreach($datos['kpis'] ?? [] as $kpi)
        @php
        $esObservacion = \Illuminate\Support\Str::startsWith(strtoupper($kpi['codigo_pregunta'] ?? ''), 'OBS_');
        @endphp
        @if ($esObservacion)
        <li>{{ $kpi['valor'] ?? 'Sin observaciones' }}</li>
        @endif
        @endforeach
    </ul>
    @endif


    <h2>🛠️ Plan de acción</h2>
    <ul>
        @foreach($datos['planes'] ?? [] as $i => $plan)
        <li>
            <strong>Punto {{ $i + 1 }}:</strong> {{ $plan['descripcion'] }}<br>
            <strong>Fecha meta:</strong> {{ $plan['fecha_cumplimiento'] ?? $plan['fecha'] ?? 'Sin fecha' }}
        </li>
        @endforeach
    </ul>

    <hr>

    <p style="font-size: 0.9em; color: #555;">
        <em>
            *Los resultados finales y por categoría son ponderados de acuerdo al peso de cada elemento.*<br>
            Si tiene alguna duda, favor escribir a Talento Humano.
        </em>
    </p>
</body>

</html>