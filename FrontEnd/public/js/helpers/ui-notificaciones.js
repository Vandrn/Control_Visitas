export function mostrarNotificacion(msg, tipo = "info") {

    const colores = {
        success: "#059669",
        error: "#dc2626",
        warning: "#d97706",
        info: "#2563eb"
    };

    const $n = $(`
        <div style="
            position:fixed; top:20px; right:20px;
            background:${colores[tipo]};
            padding:12px 16px; color:white;
            border-radius:8px; z-index:9999;
        ">
            ${msg}
        </div>
    `).appendTo("body");

    setTimeout(() => $n.fadeOut(400, () => $n.remove()), 3500);
}
