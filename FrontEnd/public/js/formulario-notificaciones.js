// Notificaciones tipo toast y mensajes

// Notificaciones tipo toast
export function mostrarNotificacion(mensaje, tipo = 'info') {
    const colores = {
        'success': '#059669',
        'error': '#dc2626',
        'warning': '#d97706',
        'info': '#2563eb'
    };

    const iconos = {
        'success': '✅',
        'error': '❌',
        'warning': '⚠️',
        'info': 'ℹ️'
    };

    const $notification = $(`
        <div class="notification" style="
            position: fixed; 
            top: 20px; 
            right: 20px; 
            z-index: 9999;
            background: ${colores[tipo]}; 
            color: white; 
            padding: 12px 16px; 
            border-radius: 8px;
            font-size: 14px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 350px; 
            min-width: 250px;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        ">
            <span style="margin-right: 8px; font-size: 16px;">${iconos[tipo]}</span>
            <span>${mensaje}</span>
        </div>
        <style>
            @keyframes slideIn {
                from { 
                    transform: translateX(100%); 
                    opacity: 0; 
                }
                to { 
                    transform: translateX(0); 
                    opacity: 1; 
                }
            }
            @keyframes slideOut {
                from { 
                    transform: translateX(0); 
                    opacity: 1; 
                }
                to { 
                    transform: translateX(100%); 
                    opacity: 0; 
                }
            }
        </style>
    `);

    // Agregar al body
    $('body').append($notification);

    // Auto-remover después de 4 segundos
    setTimeout(() => {
        $notification.css({
            'animation': 'slideOut 0.3s ease',
            'animation-fill-mode': 'forwards'
        });

        setTimeout(() => {
            $notification.remove();
        }, 300);
    }, 4000);

    // Permitir cerrar con clic
    $notification.click(function () {
        $(this).css({
            'animation': 'slideOut 0.3s ease',
            'animation-fill-mode': 'forwards'
        });
        setTimeout(() => {
            $(this).remove();
        }, 300);
    });

    return $notification;
}

// Mostrar mensaje de distancia
export function mostrarMensajeDistancia(mensaje, tipo) {
    // Remover mensaje anterior
    $('#mensaje-distancia').remove();

    // Crear nuevo mensaje
    const claseColor = {
        'success': 'background: #10b981; color: white;',
        'danger': 'background: #ef4444; color: white;',
        'warning': 'background: #f59e0b; color: white;',
        'info': 'background: #3b82f6; color: white;'
    }[tipo] || 'background: #6b7280; color: white;';

    const mensajeHtml = `
        <div id="mensaje-distancia" style="
            margin-top: 8px; 
            padding: 12px 16px; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 500;
            text-align: center;
            ${claseColor}
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        ">
            ${mensaje}
        </div>
    `;

    // Insertar después del select de tienda
    $('#CRM_ID_TIENDA').after(mensajeHtml);
}
