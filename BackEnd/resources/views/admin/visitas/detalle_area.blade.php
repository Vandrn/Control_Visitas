@extends('layouts.admin')

@section('content')
<div class="p-6 max-w-4xl mx-auto">
    <h2 class="text-xl font-semibold mb-4">Detalle de {{ $titulo }}</h2>

    @foreach($area['preguntas'] as $pregunta)
        <div class="mb-6 border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
            <h4 class="font-medium text-gray-800 mb-2">{{ $pregunta['texto'] ?? 'Pregunta sin texto' }}</h4>

            <p class="text-gray-700"><strong>Respuesta:</strong> {{ $pregunta['respuesta'] ?? 'No respondida' }}</p>

            @if(!empty($pregunta['imagenes']))
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($pregunta['imagenes'] as $img)
                        <img src="{{ $img }}" class="rounded shadow" alt="Imagen respuesta">
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
