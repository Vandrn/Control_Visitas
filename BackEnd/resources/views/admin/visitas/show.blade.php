@extends('admin.layouts.app')

@section('title', 'Detalle de Visita - ' . ($visita['tienda'] ?? 'N/A'))

@section('content')
<div class="container-fluid">
    
    {{-- Incluir partials --}}
    @include('admin.visitas.partials.header-info')
    
    {{-- �9�9 AGREGAR VALIDACI�0�7N DE DISTANCIA --}}
    @include('admin.visitas.partials.distance-validation')
    
    {{-- AGREGAR ESTA L�0�1NEA --}}
    @include('admin.visitas.partials.visual-scoring')

    @include('admin.visitas.partials.kpi')
    
    @include('admin.visitas.partials.action-plans')
</div>
@endsection

@push('styles')
<style>
    /* Estilos de impresi��n */
    @media print {
        .no-print { display: none !important; }
        .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
        .btn { display: none; }
        .accordion-button::after { display: none; }
        .accordion-collapse { display: block !important; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Funcionalidad para navegaci��n suave
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Auto-expandir primer acorde��n
    const firstAccordion = document.querySelector('.accordion-collapse');
    if (firstAccordion && !firstAccordion.classList.contains('show')) {
        firstAccordion.classList.add('show');
    }
});
</script>
@endpush