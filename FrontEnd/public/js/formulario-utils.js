// Utilidades generales
// Función debounce para limitar la frecuencia de ejecución
export function debounce(fn, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), wait);
    };
}

// Transformar valores de radio buttons (1-5 → 0.2-1)
export function transformarValoresRadio() {
    $("input[type='radio']:checked").each(function () {
        let valor = $(this).val();
        let nuevoValor = { "1": 0.2, "2": 0.4, "3": 0.6, "4": 0.8, "5": 1.0 }[valor] || valor;
        $(this).attr("data-transformado", nuevoValor);
    });
}

// ======================================================
// Manejo de selección de modalidad (Virtual / Presencial)
// ======================================================
export function bindSeleccionModalidad() {
    $(document).off('click.modalidad').on('click.modalidad', '.modalidad-btn', function () {
        // Quitar selección previa
        $('.modalidad-btn').removeClass('modalidad-activa');

        // Marcar el botón actual
        $(this).addClass('modalidad-activa');

        // Obtener valor seleccionado (virtual/presencial)
        const modo = $(this).data('modalidad');

        // Guardar valor en input oculto
        $('#modalidad_visita').val(modo);

        // Registrar en variable global (para validaciones y distancia)
        window.__modalidad_visita = modo;
        window.modalidadSeleccionada = modo;

        console.log('✅ Modalidad seleccionada:', modo);
    });
}