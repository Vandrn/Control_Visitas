import { obtenerEstructuraFinal, guardarSeccion } from "../api/api-guardar.js";
import { mostrarNotificacion } from "../helpers/ui-notificaciones.js";
import { subidaEnProceso } from "../api/api-subida.js";

export function setupNavigation() {

    // 🔥 Ocultar todo al inicio
    $("[id^='intro-'], [id^='preguntas-'], #datos, [id^='seccion-']").hide();

    // 🔥 Mostrar primera pantalla
    $("#intro").show();

    // 🔥 Detectar todas las secciones dinámicamente
    let secciones = [];

    // Intro-X (pero NO incluir #intro principal)
    $("[id^='intro-']").not("#intro").each(function () {
        secciones.push($(this).attr("id"));
    });


    // preguntas-X
    $("[id^='preguntas-']").each(function () {
        secciones.push($(this).attr("id"));
    });

    // sección final (si existe)
    if ($("#seccion-6").length) secciones.push("seccion-6");
    if ($("#seccion-7").length) secciones.push("seccion-7");

    // Ordenar por número
    secciones.sort((a, b) => {
        let n1 = parseInt(a.split("-")[1]);
        let n2 = parseInt(b.split("-")[1]);
        return n1 - n2;
    });

    // índice inicial
    let indiceActual = 0;

    function mostrarSeccion(indice) {
        secciones.forEach((id, i) => {
            $("#" + id).toggle(i === indice);
        });

        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    mostrarSeccion(indiceActual);

    // 🔵 BOTÓN EMPEZAR (intro → preguntas)
    $(document).on("click", ".btnEmpezar", function () {
        const seccion = $(this).data("seccion"); // ej: 2

        // buscar índice del intro-2
        const introId = "intro-" + seccion;
        const preguntasId = "preguntas-" + seccion;

        const idxIntro = secciones.indexOf(introId);
        const idxPreguntas = secciones.indexOf(preguntasId);

        if (idxPreguntas !== -1) {
            mostrarSeccion(idxPreguntas);
            indiceActual = idxPreguntas;
        }
    });

    // 🔵 BOTÓN SIGUIENTE (preguntas → intro siguiente o final)
    $(document).on("click", ".btnSiguiente", function (e) {
        e.preventDefault();

        if (subidaEnProceso.value) {
            return mostrarNotificacion("⏳ Espera a que termine la subida", "warning");
        }

        // última sección → guardar
        if (indiceActual === secciones.length - 1) {
            const data = obtenerEstructuraFinal();
            guardarSeccion(data);
            return;
        }

        indiceActual++;
        mostrarSeccion(indiceActual);
    });
}
