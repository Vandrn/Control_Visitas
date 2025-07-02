{{-- Modal para mostrar imágenes --}}
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Evidencia Fotográfica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Evidencia" class="img-fluid rounded">
                <div id="modalImageInfo" class="mt-3">
                    <p class="text-muted mb-0" id="modalImageSection"></p>
                </div>
            </div>
            <div class="modal-footer">
                <a id="downloadImageBtn" href="" download class="btn btn-primary">
                    <i class="fas fa-download"></i> Descargar Imagen
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- Estilos CSS personalizados --}}
<style>
/* Estilos generales */
.stars {
    font-size: 0.9rem;
    line-height: 1;
}

.stars i {
    margin-right: 2px;
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeInUp 0.5s ease-out;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }
.card:nth-child(5) { animation-delay: 0.5s; }

/* Hover effects */
.card:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease-in-out;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
        width: 100%;
    }
    
    .btn-group .btn {
        margin-bottom: 0.5rem;
        border-radius: 0.375rem !important;
    }
    
    .stars {
        font-size: 0.8rem;
    }
    
    .card-title {
        font-size: 1rem;
    }
    
    .display-4 {
        font-size: 2rem;
    }
}

/* Mejoras para impresión */
@media print {
    .no-print, 
    .btn, 
    .modal,
    .accordion-button::after {
        display: none !important;
    }
    
    .accordion-collapse {
        display: block !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem !important;
    }
    
    .progress {
        background-color: #e9ecef !important;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
    
    .progress-bar {
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
    
    .text-primary, .text-success, .text-warning, .text-danger, .text-info {
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
    
    .bg-primary, .bg-success, .bg-warning, .bg-danger, .bg-info {
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }
    
    /* Forzar salto de página antes de planes de acción */
    #planes {
        page-break-before: always;
    }
    
    /* Evitar que las tarjetas se corten */
    .border-left-primary,
    .border-left-success,
    .border-left-warning,
    .border-left-danger,
    .border-left-info,
    .border-left-secondary {
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
        page-break-inside: avoid;
    }
}

/* Estilos para navegación sticky */
.sticky-navigation {
    position: sticky;
    top: 20px;
    z-index: 1000;
}

/* Efectos de botones activos */
.btn-group a.active,
.btn-group-vertical a.active {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

/* Tooltips */
[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Mejoras para badges */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}

/* Loading states */
.loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Mejoras para acordeones */
.accordion-button:not(.collapsed) {
    background-color: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

/* Estilos para evidencias fotográficas */
.img-fluid:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease;
    cursor: pointer;
}

/* Estilos para progress bars animadas */
.progress-bar {
    transition: width 0.6s ease;
}

/* Responsive text */
@media (max-width: 576px) {
    .h1, .h2, .h3, .h4, .h5, .h6,
    h1, h2, h3, h4, h5, h6 {
        font-size: calc(1rem + 0.5vw);
    }
    
    .display-4 {
        font-size: calc(1.5rem + 1vw);
    }
    
    .btn-group-vertical .btn {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
}

/* Dark mode support (opcional) */
@media (prefers-color-scheme: dark) {
    .bg-light {
        background-color: #f8f9fa !important;
        color: #212529;
    }
}
</style>

{{-- JavaScript para funcionalidad --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Función para mostrar modal de imagen
    window.showImageModal = function(imageUrl, sectionTitle) {
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        const modalImage = document.getElementById('modalImage');
        const modalSection = document.getElementById('modalImageSection');
        const downloadBtn = document.getElementById('downloadImageBtn');
        
        modalImage.src = imageUrl;
        modalImage.alt = 'Evidencia de ' + sectionTitle;
        modalSection.textContent = 'Sección: ' + sectionTitle;
        downloadBtn.href = imageUrl;
        downloadBtn.download = 'evidencia_' + sectionTitle.toLowerCase().replace(/\s+/g, '_') + '.jpg';
        
        modal.show();
    };
    
    // Inicializar tooltips si Bootstrap está disponible
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Navegación suave mejorada
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // Calcular offset para header sticky si existe
                const offset = 100;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - offset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
                
                // Expandir acordeón si es necesario
                const accordion = targetElement.querySelector('.accordion-collapse');
                if (accordion && !accordion.classList.contains('show')) {
                    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        const bsCollapse = new bootstrap.Collapse(accordion, {
                            toggle: true
                        });
                    }
                }
            }
        });
    });
    
    // Lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('loading');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
    
    // Función para imprimir mejorada
    window.printPage = function() {
        // Expandir todos los acordeones antes de imprimir
        const accordions = document.querySelectorAll('.accordion-collapse:not(.show)');
        accordions.forEach(accordion => {
            accordion.classList.add('show');
        });
        
        // Esperar un momento para que se expandan
        setTimeout(() => {
            window.print();
            
            // Restaurar estado original después de imprimir
            setTimeout(() => {
                accordions.forEach(accordion => {
                    accordion.classList.remove('show');
                });
            }, 1000);
        }, 500);
    };
    
    // Mejorar el comportamiento del botón de imprimir
    const printBtns = document.querySelectorAll('button[onclick="window.print()"]');
    printBtns.forEach(btn => {
        btn.setAttribute('onclick', 'printPage()');
    });
    
    // Animaciones al hacer scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observar elementos para animaciones
    document.querySelectorAll('.card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
});
</script>