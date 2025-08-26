@extends('admin.layouts.app')

@section('content')
<div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-purple-600 rounded-lg shadow-lg mb-6">
    <div class="p-6 flex flex-col md:flex-row justify-between items-start md:items-center">
        <div>
            <h3 class="text-2xl font-bold text-black">
                {{ isset($area['tienda']) ? $area['tienda'] : (isset($area['nombre_seccion']) ? ucfirst($area['nombre_seccion']) : 'Área sin nombre') }}
            </h3>
            <span class="text-indigo-100 flex items-center space-x-2">
                <i class="fas fa-layer-group"></i>
                <span>Detalle del área {{ $area['nombre_seccion'] ?? $titulo }}</span>
            </span>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="{{ route('admin.visita.show', $idVisita) }}"
                class="inline-flex items-center px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-lg shadow-sm transition-colors duration-150 ease-in-out">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver a la visita
            </a>
        </div>
    </div>
</div>
<div class="p-6 max-w-4xl mx-auto">
    <h2 class="text-xl font-semibold mb-4">Detalle de {{ $titulo }}</h2>

    @foreach($area['preguntas'] as $pregunta)
        <div class="mb-6 border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
            <h4 class="font-medium text-gray-800 mb-2">
                @php
                    // Normaliza el nombre de la sección para buscar la clave correcta
                    $claveSeccion = strtolower(str_replace([' ', 'á','é','í','ó','ú','Á','É','Í','Ó','Ú'],
                        ['','a','e','i','o','u','a','e','i','o','u'], $area['nombre_seccion'] ?? ''));
                    // Buscar texto por código en todo el array si no se encuentra por sección
                    $textoPregunta = null;
                    if(isset($pregunta['codigo_pregunta'])) {
                        if(isset($textosPreguntas[$claveSeccion][$pregunta['codigo_pregunta']])) {
                            $textoPregunta = $textosPreguntas[$claveSeccion][$pregunta['codigo_pregunta']];
                        } else {
                            // Buscar en todas las secciones
                            foreach($textosPreguntas as $seccion) {
                                if(isset($seccion[$pregunta['codigo_pregunta']])) {
                                    $textoPregunta = $seccion[$pregunta['codigo_pregunta']];
                                    break;
                                }
                            }
                        }
                    }
                @endphp
                @if(!empty($textoPregunta))
                    {{ $textoPregunta }}
                @elseif(isset($pregunta['codigo_pregunta']) && strpos($pregunta['codigo_pregunta'], 'OBS_') === 0)
                    Observaciones del área {{ $area['nombre_seccion'] ?? '' }}
                @elseif(isset($pregunta['codigo_pregunta']))
                    {{ $pregunta['codigo_pregunta'] }}
                @else
                    Pregunta sin texto
                @endif
            </h4>

            <p class="text-gray-700"><strong>Respuesta:</strong> {{ $pregunta['respuesta'] ?? 'No respondida' }}</p>

            @if(!empty($pregunta['imagenes']))
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($pregunta['imagenes'] as $img)
                        <img src="{{ is_array($img) ? ($img['url'] ?? $img[0] ?? '') : $img }}" class="rounded shadow" alt="Imagen respuesta">
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach

    @if(!empty($area['observacion']))
        <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <p class="text-sm text-yellow-800"><strong>Observación:</strong> {{ $area['observacion'] }}</p>
        </div>
    @endif
</div>
@endsection
