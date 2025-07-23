@component('mail::message')
# 📝 Resultado de la visita a {{ $datos['tienda'] }}

**Fecha:** {{ $datos['fecha_hora_fin'] }}  
**Zona:** {{ $datos['zona'] }}  
**País:** {{ $datos['pais'] }}  
**Puntaje Total:** {{ $datos['puntos_totales'] ?? 'N/A' }} (equivale a {{ $datos['estrellas'] ?? 'N/A' }} estrellas)

---

## 📊 Resultado por área

@foreach($datos['resumen_areas'] ?? [] as $area)
- **{{ $area['nombre'] }}:** {{ $area['puntos'] }} puntos (equivalente a {{ $area['estrellas'] }} estrellas)
@endforeach

---

## ✅ Resultado detallado por pregunta

@foreach($datos['secciones'] ?? [] as $seccion)
<br>
### Área {{ ucfirst($seccion['nombre_seccion']) }}

@foreach($seccion['preguntas'] as $preg)
@php
    $esObservacion = \Illuminate\Support\Str::startsWith($preg['codigo_pregunta'], 'OBS_');
    $nombre = \App\Helpers\PreguntaHelper::nombreBonito($preg['codigo_pregunta']);
@endphp

@if ($esObservacion)
**Observaciones:**  
{{ $preg['respuesta'] ?? 'Sin observaciones' }}

@else
- {{ $nombre }}: 
@php
    $valor = is_numeric($preg['respuesta']) ? floatval($preg['respuesta']) : null;
@endphp
@if (!is_null($valor))
    {{ str_repeat('★', intval($valor / 0.2)) }}
@else
    {{ $preg['respuesta'] ?? 'N/A' }}
@endif

{{-- Imágenes asociadas --}}
@if (!empty($preg['imagenes']))
<br>
@foreach ($preg['imagenes'] as $img)
<div style="text-align: center; margin-bottom: 10px;">
    <a href="{{ $img }}" target="_blank">📸 Ver imagen</a>
</div>
@endforeach
@endif

@endif
@endforeach

<hr>
@endforeach

---

## 🧮 KPIs

@foreach($datos['kpis'] ?? [] as $kpi)
@php
    $nombre = \App\Helpers\PreguntaHelper::nombreBonito($kpi['codigo_pregunta']);
@endphp
- {{ $nombre }}: {{ $kpi['valor'] }} (variación: {{ $kpi['variacion'] ?? '0' }})
@endforeach

---

## 🛠️ Plan de acción

@foreach($datos['planes'] ?? [] as $i => $plan)
- Punto {{ $i + 1 }}: {{ $plan['descripcion'] }}  
  **Fecha meta:** {{ $plan['fecha_cumplimiento'] ?? $plan['fecha'] ?? 'Sin fecha' }}
@endforeach

---

> *Los resultados finales y por categoría son ponderados de acuerdo al peso de cada elemento.*  
> Si tiene alguna duda, favor escribir a Talento Humano.
@endcomponent
