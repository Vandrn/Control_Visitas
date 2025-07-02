{{-- Navegación sticky para saltar entre secciones --}}
<div class="card mb-4 no-print">
    <div class="card-body">
        <h6 class="card-title mb-3">
            <i class="fas fa-compass"></i> Navegación Rápida
        </h6>
        
        <div class="row">
            <div class="col-12">
                <div class="btn-group-vertical btn-group-sm d-md-none w-100" role="group">
                    {{-- Mobile: Botones verticales --}}
                    <a href="#operaciones" class="btn btn-outline-primary">
                        <i class="fas fa-cogs"></i> Operaciones
                    </a>
                    <a href="#administracion" class="btn btn-outline-success">
                        <i class="fas fa-clipboard-list"></i> Administración
                    </a>
                    <a href="#producto" class="btn btn-outline-warning">
                        <i class="fas fa-box"></i> Producto
                    </a>
                    <a href="#personal" class="btn btn-outline-info">
                        <i class="fas fa-users"></i> Personal
                    </a>
                    <a href="#kpis" class="btn btn-outline-danger">
                        <i class="fas fa-chart-line"></i> KPIs
                    </a>
                    <a href="#planes" class="btn btn-outline-secondary">
                        <i class="fas fa-tasks"></i> Planes de Acción
                    </a>
                </div>
                
                <div class="btn-group btn-group-sm d-none d-md-flex" role="group">
                    {{-- Desktop: Botones horizontales --}}
                    <a href="#operaciones" class="btn btn-outline-primary">
                        <i class="fas fa-cogs"></i> Operaciones
                    </a>
                    <a href="#administracion" class="btn btn-outline-success">
                        <i class="fas fa-clipboard-list"></i> Administración
                    </a>
                    <a href="#producto" class="btn btn-outline-warning">
                        <i class="fas fa-box"></i> Producto
                    </a>
                    <a href="#personal" class="btn btn-outline-info">
                        <i class="fas fa-users"></i> Personal
                    </a>
                    <a href="#kpis" class="btn btn-outline-danger">
                        <i class="fas fa-chart-line"></i> KPIs
                    </a>
                    <a href="#planes" class="btn btn-outline-secondary">
                        <i class="fas fa-tasks"></i> Planes de Acción
                    </a>
                </div>
            </div>
        </div>
        
        {{-- Indicador de progreso de scroll --}}
        <div class="mt-3">
            <div class="progress" style="height: 5px;">
                <div id="scrollProgress" class="progress-bar bg-primary" role="progressbar" style="width: 0%"></div>
            </div>
            <small class="text-muted">Progreso de lectura</small>
        </div>
    </div>
</div>

{{-- Script para progreso de scroll --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Actualizar barra de progreso de scroll
    function updateScrollProgress() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / docHeight) * 100;
        
        const progressBar = document.getElementById('scrollProgress');
        if (progressBar) {
            progressBar.style.width = scrollPercent + '%';
        }
    }
    
    // Activar botón de navegación actual
    function updateActiveNavButton() {
        const sections = ['operaciones', 'administracion', 'producto', 'personal', 'kpis', 'planes'];
        let activeSection = null;
        
        sections.forEach(section => {
            const element = document.getElementById(section);
            if (element) {
                const rect = element.getBoundingClientRect();
                if (rect.top <= 100 && rect.bottom >= 100) {
                    activeSection = section;
                }
            }
        });
        
        // Remover clase active de todos los botones
        document.querySelectorAll('.btn-group a, .btn-group-vertical a').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Agregar clase active al botón actual
        if (activeSection) {
            document.querySelectorAll(`a[href="#${activeSection}"]`).forEach(btn => {
                btn.classList.add('active');
            });
        }
    }
    
    // Event listeners
    window.addEventListener('scroll', function() {
        updateScrollProgress();
        updateActiveNavButton();
    });
    
    // Inicializar
    updateScrollProgress();
    updateActiveNavButton();
});
</script>