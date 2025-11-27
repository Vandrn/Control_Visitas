import { initModalidad } from "./core/modalidad.js";
import { setupNavigation } from "./core/navigation.js";
import { mantenerSesionActiva } from "./core/session.js";
import { cargarPaises } from "./api/api-ubicaciones.js";
import { setupSubidaIncremental } from "./api/api-subida.js";

$(document).ready(() => {
    initModalidad();
    cargarPaises();
    setupSubidaIncremental();
    setupNavigation();
    mantenerSesionActiva();
});
