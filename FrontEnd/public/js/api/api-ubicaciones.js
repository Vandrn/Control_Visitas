import { validarDistanciaTienda } from "../helpers/geolocalizacion.js";

export function cargarPaises() {
    $.get("/retail/paises", data => {
        $("#pais").append(`<option value="">Seleccione un país</option>`);
        data.forEach(p =>
            $("#pais").append(`<option value="${p.value}" data-nombre="${p.label}">${p.label}</option>`)
        );
    });

    $("#pais").change(() => cargarZonas());
}

export function cargarZonas() {
    let pais = $("#pais").val();
    $("#zona").empty().append(`<option value="">Seleccione zona</option>`);

    $.get(`/retail/zonas/${pais}`, zonas => {
        zonas.forEach(z => $("#zona").append(`<option value="${z}">${z}</option>`));
    });

    $("#zona").change(() => cargarTiendas());
}

export function cargarTiendas() {
    let pais = $("#pais").val();
    let zona = $("#zona").val();

    $("#CRM_ID_TIENDA").empty().append(`<option value="">Seleccione tienda</option>`);

    $.get(`/retail/tiendas/${pais}/${zona}`, tiendas => {
        tiendas.forEach(t => {
            $("#CRM_ID_TIENDA").append(`
                <option value="${t.TIENDA}" data-geo="${t.GEO}" data-ubicacion="${t.UBICACION}">
                    ${t.TIENDA}
                </option>
            `);
        });
        $("#CRM_ID_TIENDA").off().on("change", validarDistanciaTienda);
    });
}
