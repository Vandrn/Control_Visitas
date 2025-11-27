import { mostrarMensajeDistancia } from "./ui-previews.js";

export function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) ** 2;

    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

export function obtenerUbicacionUsuario() {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        });
    });
}

export async function validarDistanciaTienda() {
     // Si es visita virtual → NO mostrar distancia
    const modalidad = $("#modalidad_visita").val();
    if (modalidad === "virtual") {
        $("#mensaje-distancia").remove();
        return;
    }
    
    const option = $("#CRM_ID_TIENDA option:selected");

    // Limpiar mensaje previo
    $("#mensaje-distancia").remove();

    if (!option.val()) return;

    const geo = option.data("geo");
    if (!geo) {
        mostrarMensajeDistancia("⚠️ No se encontraron coordenadas para esta tienda", "warning");
        return;
    }

    // Mostrar mensaje de carga como antes
    mostrarMensajeDistancia("📍 Verificando tu ubicación...", "info");

    try {
        // Obtener ubicación del usuario
        const pos = await obtenerUbicacionUsuario();
        const latUsuario = pos.coords.latitude;
        const lonUsuario = pos.coords.longitude;

        // Parse coordenadas tienda
        const [latTienda, lonTienda] = geo.split(",").map(Number);

        const distancia = calcularDistancia(
            latUsuario,
            lonUsuario,
            latTienda,
            lonTienda
        );

        const d = Math.round(distancia);

        if (d <= 50) {
            mostrarMensajeDistancia(
                `✅ Te encuentras a ${d} metros de la tienda`,
                "success"
            );
        } else {
            mostrarMensajeDistancia(
                `❌ Te encuentras a ${d} metros de la tienda (muy lejos)`,
                "danger"
            );
        }
    } catch (e) {
        mostrarMensajeDistancia("❌ No se pudo obtener tu ubicación", "danger");
    }
}

