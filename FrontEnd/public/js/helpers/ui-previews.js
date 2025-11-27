export function mostrarIndicadorSubida($input, show, field, msg = "") {
    let div = $input.parent().find(".upload-indicator");

    if (show) {
        if (!div.length) {
            div = $(`<div class="upload-indicator">${msg} ${field}</div>`);
            $input.after(div);
        } else {
            div.text(`${msg} ${field}`);
        }
    } else {
        div.remove();
    }
}

export function mostrarMensajeDistancia(mensaje, tipo) {
    $('#mensaje-distancia').remove();

    const clase = {
        'success': 'distancia-success',
        'danger': 'distancia-danger',
        'warning': 'distancia-warning',
        'info': 'distancia-info'
    }[tipo];

    const html = `
        <div id="mensaje-distancia" class="${clase}">
            ${mensaje}
        </div>
    `;

    $('#CRM_ID_TIENDA').after(html);
}

