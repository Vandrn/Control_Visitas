// =============================================================
// ui.js — Notificaciones toast, indicadores de subida y previews
// Depende de: config.js (no tiene dependencias directas de estado)
// =============================================================

var NOTIF_COLORES = {
    success: '#059669',
    error:   '#dc2626',
    warning: '#d97706',
    info:    '#2563eb',
    loading: '#6366f1'
};

var NOTIF_ICONOS = {
    success: '✅',
    error:   '❌',
    warning: '⚠️',
    info:    'ℹ️',
    loading: '⏳'
};

// Inyectar animaciones CSS una sola vez
$('<style>\
@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}\
@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}\
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}\
.loading-icon{animation:spin 1s linear infinite!important}\
@keyframes spinUpload{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}\
</style>').appendTo('head');

function mostrarNotificacion(mensaje, tipo, permanente) {
    tipo      = tipo      || 'info';
    permanente = permanente || false;

    var $n = $(
        '<div class="notification" style="\
            position:fixed;top:20px;right:20px;z-index:9999;\
            background:' + NOTIF_COLORES[tipo] + ';\
            color:white;padding:12px 16px;border-radius:8px;\
            font-size:14px;box-shadow:0 4px 12px rgba(0,0,0,.15);\
            max-width:350px;min-width:250px;\
            animation:slideIn .3s ease;\
            display:flex;align-items:center;gap:8px;\
            font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">\
            <span style="font-size:16px;flex-shrink:0;" class="notif-icon">' + NOTIF_ICONOS[tipo] + '</span>\
            <span class="notif-message">' + mensaje + '</span>\
        </div>'
    );

    $('body').append($n);
    $n.data('permanente', permanente);

    if (tipo === 'loading') {
        $n.find('.notif-icon').addClass('loading-icon');
    }

    if (!permanente) {
        setTimeout(function() { cerrarNotificacion($n); }, 4000);
    }

    $n.on('click', function() { cerrarNotificacion($(this)); });
    return $n;
}

function cerrarNotificacion($n) {
    $n.css({ animation: 'slideOut .3s ease', 'animation-fill-mode': 'forwards' });
    setTimeout(function() { if ($n.length) $n.remove(); }, 300);
}

function actualizarNotificacionPermanente($n, nuevoMensaje, nuevoTipo) {
    $n.find('.notif-message').text(nuevoMensaje);
    if (nuevoTipo) {
        $n.css('background', NOTIF_COLORES[nuevoTipo]);
        var $icon = $n.find('.notif-icon');
        $icon.text(NOTIF_ICONOS[nuevoTipo]).removeClass('loading-icon');
        if (nuevoTipo === 'loading') $icon.addClass('loading-icon');
    }
}

function mostrarIndicadorSubida($input, show, fieldName, mensaje) {
    mensaje = mensaje || 'Subiendo';
    var $ind = $input.parent().find('.upload-indicator');
    var spinnerHtml =
        '<div class="spinner" style="\
            width:16px;height:16px;\
            border:2px solid #d1fae5;\
            border-top:2px solid #059669;\
            border-radius:50%;\
            animation:spinUpload 1s linear infinite;\
            margin-right:8px;"></div>';

    if (show && $ind.length === 0) {
        $ind = $(
            '<div class="upload-indicator" style="\
                margin-top:8px;color:#059669;font-size:14px;\
                display:flex;align-items:center;\
                background:#f0f9ff;padding:8px 12px;\
                border-radius:8px;border-left:4px solid #059669;">'
            + spinnerHtml + ' ' + mensaje + ' ' + fieldName + '...</div>'
        );
        $input.after($ind);
    } else if (show && $ind.length > 0) {
        $ind.html(spinnerHtml + ' ' + mensaje + ' ' + fieldName + '...');
    } else if (!show && $ind.length > 0) {
        $ind.remove();
    }
}

function mostrarPreviewImagen($input, url, fieldName) {
    var $prev = $input.parent().find('.image-preview');
    if ($prev.length === 0) {
        $prev = $(
            '<div class="image-preview" style="\
                margin-top:12px;padding:12px;\
                background:#f0f9ff;border-radius:8px;\
                border:2px solid #059669;">\
                <img src="' + url + '" alt="' + fieldName + '" style="\
                    max-width:120px;max-height:120px;border-radius:8px;\
                    display:block;margin:0 auto 8px;">\
                <div style="font-size:12px;color:#059669;text-align:center;font-weight:bold;">\
                    Imagen guardada en servidor</div>\
                <div class="prev-url" style="\
                    font-size:11px;color:#6b7280;text-align:center;margin-top:4px;">\
                    URL: ' + url.substring(0, 40) + '...</div>\
            </div>'
        );
        $input.after($prev);
    } else {
        $prev.find('img').attr('src', url);
        $prev.find('.prev-url').text('URL: ' + url.substring(0, 40) + '...');
    }
}

function mostrarMensajeDistancia(mensaje, tipo) {
    $('#mensaje-distancia').remove();
    var estilos = {
        success: 'background:#10b981;color:white;',
        danger:  'background:#ef4444;color:white;',
        warning: 'background:#f59e0b;color:white;',
        info:    'background:#3b82f6;color:white;'
    };
    var estilo = estilos[tipo] || 'background:#6b7280;color:white;';
    $('#CRM_ID_TIENDA').after(
        '<div id="mensaje-distancia" style="\
            margin-top:8px;padding:12px 16px;border-radius:8px;\
            font-size:14px;font-weight:500;text-align:center;\
            box-shadow:0 2px 4px rgba(0,0,0,.1);' + estilo + '">'
        + mensaje + '</div>'
    );
}
