import { mostrarNotificacion } from "../helpers/ui-notificaciones.js";

export function mantenerSesionActiva() {

    let fallos = 0;

    setInterval(() => {
        fetch("/keep-alive", {
            method: "GET",
            credentials: "same-origin"
        }).then(r => {
            if (!r.ok) throw new Error();
            fallos = 0;
        }).catch(() => {
            fallos++;
            if (fallos >= 2) {
                mostrarNotificacion("⚠️ Tu sesión expiró. Recarga la página.", "warning");
            }
        });
    }, 3 * 60 * 1000);
}
