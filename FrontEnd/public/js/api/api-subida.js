import { comprimirImagenCliente } from "../helpers/compresion.js";
import { mostrarIndicadorSubida } from "../helpers/ui-previews.js";
import { mostrarNotificacion } from "../helpers/ui-notificaciones.js";

export let imagenesSubidas = {};
export let subidaEnProceso = { value: false };

export function setupSubidaIncremental() {
    $('input[name^="IMG_"]').on("change", async function (e) {

        const $input = $(this);
        const raw = $input.attr("name");
        const baseField = raw.replace(/\[\]$/, "");
        const files = Array.from(e.target.files);

        for (let i = 0; i < files.length; i++) {

            if (!files[i].type.startsWith("image/")) {
                mostrarNotificacion("❌ Archivo no es imagen", "error");
                continue;
            }

            if (!imagenesSubidas[baseField]) {
                imagenesSubidas[baseField] = [];
            }

            const index = imagenesSubidas[baseField].length;
            const fieldName = `${baseField}_${String(index + 1).padStart(2, "0")}`;

            subidaEnProceso.value = true;

            try {
                mostrarIndicadorSubida($input, true, fieldName, "Comprimiendo...");

                const imagen = await comprimirImagenCliente(files[i]);

                mostrarIndicadorSubida($input, true, fieldName, "Subiendo...");

                const url = await subirImagen(imagen, fieldName);
                imagenesSubidas[baseField].push(url);

                mostrarIndicadorSubida($input, false, fieldName);

            } catch (err) {
                mostrarNotificacion("❌ Error al subir imagen", "error");
            }

            subidaEnProceso.value = false;
        }
    });
}

async function subirImagen(blob, fieldName) {

    const form = new FormData();
    form.append("image", blob, `${fieldName}.jpg`);
    form.append("field_name", fieldName);

    const token = document.querySelector('meta[name="csrf-token"]').content;

    const r = await fetch("/retail/subir-imagen-incremental", {
        method: "POST",
        headers: { "X-CSRF-TOKEN": token },
        body: form
    });

    const res = await r.json();

    if (!res.success) throw new Error(res.error);
    return res.url;
}
