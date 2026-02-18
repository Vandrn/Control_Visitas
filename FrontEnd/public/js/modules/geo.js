// =============================================================
// geo.js — Geolocalización y validación de distancia a tienda
// Depende de: ui.js (mostrarMensajeDistancia, mostrarNotificacion)
// =============================================================

function calcularDistancia(lat1, lng1, lat2, lng2) {
    var R    = 6371000;
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function obtenerUbicacionUsuario() {
    return new Promise(function(resolve, reject) {
        if (!navigator.geolocation) {
            return reject(new Error('Geolocalización no soportada'));
        }
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        });
    });
}

async function validarDistanciaTienda() {
    if (window.__modalidad_visita === 'virtual') {
        $('#mensaje-distancia').remove();
        return;
    }

    var tiendaSelect   = document.getElementById('CRM_ID_TIENDA');
    var selectedOption = tiendaSelect.options[tiendaSelect.selectedIndex];

    $('#mensaje-distancia').remove();

    if (!selectedOption.value || selectedOption.value === '') return;

    var geoData = selectedOption.getAttribute('data-geo');
    if (!geoData) {
        mostrarMensajeDistancia('No se encontraron coordenadas para esta tienda', 'warning');
        return;
    }

    mostrarMensajeDistancia('Verificando tu ubicación...', 'info');

    try {
        var position = await obtenerUbicacionUsuario();
        var partes   = geoData.split(',').map(Number);
        var distancia = calcularDistancia(
            position.coords.latitude,
            position.coords.longitude,
            partes[0],
            partes[1]
        );
        var d = Math.round(distancia);

        if (d <= 50) {
            mostrarMensajeDistancia('Te encuentras a ' + d + ' metros de la tienda', 'success');
        } else {
            mostrarMensajeDistancia('Te encuentras a ' + d + ' metros de la tienda (muy lejos)', 'danger');
        }
    } catch (err) {
        console.error('Error obteniendo ubicación:', err);
        mostrarMensajeDistancia('No se pudo obtener tu ubicación', 'danger');
    }
}
