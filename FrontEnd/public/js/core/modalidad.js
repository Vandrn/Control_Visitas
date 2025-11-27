import { mostrarNotificacion } from "../helpers/ui-notificaciones.js";

export let modalidadSeleccionada = "";

export function initModalidad() {
    // escucha botones
    $(document).on("click", ".modalidad-btn", function () {

        $(".modalidad-btn").removeClass("modalidad-activa");
        $(this).addClass("modalidad-activa");

        modalidadSeleccionada = $(this).data("modalidad");
        $("#modalidad_visita").val(modalidadSeleccionada);

        checkCorreoYModalidad();
    });

    // correo dinámico
    $("#correo_tienda_select, #correo_tienda_otro").on("change input", checkCorreoYModalidad);

    $("<style>.modalidad-activa{background:#e6b200;color:#fff;box-shadow:0 2px 8px #e6b20080;}</style>").appendTo("head");

    let sel = $("#correo_tienda_select");
    let otro = $("#correo_tienda_otro");

    if (sel.length && otro.length) {
        sel.on("change", () => {
            if (sel.val() === "otro") {
                otro.show().prop("required", true);
            } else {
                otro.hide().prop("required", false).val("");
            }
        });

        // inicializa
        if (sel.val() === "otro") {
            otro.show().prop("required", true);
        } else {
            otro.hide().prop("required", false);
        }
    }
}

// valida correo
export function checkCorreoYModalidad() {
    let correoVal = getCorreo();

    if (!correoVal.match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/)) return false;
    if (!modalidadSeleccionada) return false;

    return true;
}

export function getCorreo() {
    let sel = $("#correo_tienda_select");
    if (sel.length) {
        if (sel.val() === "otro") return $("#correo_tienda_otro").val();
        return sel.val();
    }
    return $("#correo_tienda").val();
}
