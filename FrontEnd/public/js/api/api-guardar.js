import { imagenesSubidas } from "./api-subida.js";
import { mapaCampos } from "../data/mapeos.js";
import { mostrarNotificacion } from "../helpers/ui-notificaciones.js";

export function obtenerEstructuraFinal() {

    let datos = {
        session_id: crypto.randomUUID(),
        correo_realizo: $("#correo_tienda").val(),
        lider_zona: $("#jefe_zona").val(),
        tienda: $("#CRM_ID_TIENDA").val(),
        pais: $("#pais option:selected").data("nombre"),
        zona: $("#zona").val(),
        ubicacion: $("#ubicacion").val(),
        fecha_hora_inicio: $("#fecha_inicio").val(),
        fecha_hora_fin: new Date().toISOString(),
        imagenes: imagenesSubidas
    };

    return datos;
}

export function guardarSeccion(datos) {

    fetch("/retail/guardar-seccion", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(datos)
    })
        .then(r => r.json())
        .then(() => mostrarNotificacion("✅ Guardado exitoso", "success"))
        .catch(() => mostrarNotificacion("❌ Error al guardar", "error"));
}
