@extends('admin.layouts.app')

@section('title', 'Imágenes de Visita - ' . ($infoVisita['tienda'] ?? 'N/A'))

@section('content')
<div class="container-fluid">
    
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Imágenes - {{ $infoVisita['tienda'] }}</h1>
                </div>
                
                <div class="text-sm text-gray-600">
                    <span>{{ $infoVisita['pais'] }} • {{ $infoVisita['zona'] }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ \Carbon\Carbon::parse($infoVisita['fecha'])->format('d/m/Y H:i') }}</span>
                    <span class="mx-2">•</span>
                    <span>{{ $infoVisita['evaluador'] }}</span>
                </div>
            </div>
            
            <div class="mt-4 lg:mt-0">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ count($imagenes) }} imagen{{ count($imagenes) != 1 ? 'es' : '' }}
                </span>
            </div>
        </div>
    </div>

    @if(count($imagenes) > 0)
        <!-- Grid de Imágenes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($imagenes as $index => $imagen)
                <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Imagen -->
                    <div class="aspect-w-16 aspect-h-12 relative">
                        <img src="{{ $imagen['url'] }}" 
                             alt="{{ $imagen['titulo'] }}"
                             class="w-full h-48 object-cover cursor-pointer hover:opacity-90 transition-opacity"
                             onclick="openImageModal('{{ $imagen['url'] }}', '{{ $imagen['titulo'] }}', '{{ $imagen['observaciones'] ?? '' }}', {{ $index }})">
                        
                        <!-- Overlay con botones -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-30 transition-opacity flex items-center justify-center opacity-0 hover:opacity-100">
                            <div class="flex space-x-2">
                                <button onclick="openImageModal('{{ $imagen['url'] }}', '{{ $imagen['titulo'] }}', '{{ $imagen['observaciones'] ?? '' }}', {{ $index }})"
                                        class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 p-2 rounded-full transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </button>
                                
                                <a href="{{ $imagen['url'] }}" download 
                                   class="bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 p-2 rounded-full transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información -->
                    <div class="p-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $imagen['titulo'] }}</h3>
                        
                        @if($imagen['observaciones'])
                            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded">
                                <strong>Observaciones:</strong><br>
                                {{ $imagen['observaciones'] }}
                            </p>
                        @else
                            <p class="text-sm text-gray-400 italic">Sin observaciones</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Estado vacío -->
        <div class="bg-white shadow rounded-lg p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">No hay imágenes</h3>
            <p class="mt-2 text-sm text-gray-500">
                Esta visita no tiene imágenes asociadas en las columnas IMG_ de BigQuery.
            </p>
        </div>
    @endif

</div>

<!-- Modal para ver imagen en grande -->
<div id="imageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 lg:w-3/4 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900"></h3>
            <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="text-center">
            <img id="modalImage" src="" alt="" class="max-w-full max-h-96 mx-auto rounded-lg shadow">
        </div>
        
        <div id="modalObservations" class="mt-4 p-3 bg-gray-50 rounded hidden">
            <strong class="text-sm text-gray-900">Observaciones:</strong>
            <p id="modalObservationsText" class="text-sm text-gray-600 mt-1"></p>
        </div>
        
        <div class="mt-6 flex justify-center space-x-3">
            <a id="modalDownload" href="" download 
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Descargar
            </a>
            
            <button onclick="closeImageModal()" 
                    class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm font-medium rounded-md">
                Cerrar
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openImageModal(url, title, observations, index) {
    const modal = document.getElementById('imageModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalImage = document.getElementById('modalImage');
    const modalObservations = document.getElementById('modalObservations');
    const modalObservationsText = document.getElementById('modalObservationsText');
    const modalDownload = document.getElementById('modalDownload');
    
    modalTitle.textContent = title;
    modalImage.src = url;
    modalImage.alt = title;
    modalDownload.href = url;
    
    if (observations && observations.trim() !== '') {
        modalObservationsText.textContent = observations;
        modalObservations.classList.remove('hidden');
    } else {
        modalObservations.classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>
@endpush