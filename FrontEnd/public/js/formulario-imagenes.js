// Subida y manejo de imágenes
export const imagenesSubidas = {}; // antes: let imagenesSubidas = {}
export let subidaEnProceso = false; // antes: let subidaEnProceso = false

// Validar imágenes faltantes en una sección
export function validarImagenesFaltantes(seccionActual, imagenesSubidas) {
    let imagenesFaltantes = [];
    seccionActual.find("input[type='file'][required]").each(function (idx) {
        const input = this;
        const fieldName = input.name.replace(/\[\]$/, '');
        let falta = false;
        if (input.files.length === 0) {
            falta = true;
        } else {
            const imagenesAsociadas = Object.keys(imagenesSubidas).filter(k => k.startsWith(fieldName));
            if (imagenesAsociadas.length === 0) {
                falta = true;
            }
        }
        if (falta) {
            // Buscar label asociada
            let label = $(input).closest('.form-group, .mb-4, .mb-3').find('label').first().text().trim();
            if (!label) {
                // Si no hay label, usar placeholder si existe
                if (input.placeholder) {
                    label = input.placeholder;
                } else {
                    // Si no hay placeholder, extraer número de la pregunta del nombre técnico
                    let match = fieldName.match(/(\d{2,})$/);
                    if (match) {
                        label = `Pregunta ${parseInt(match[1], 10)}`;
                    } else {
                        label = fieldName;
                    }
                }
            }
            imagenesFaltantes.push(label);
        }
    });
    return imagenesFaltantes;
}

// Configurar subida incremental automática con compresión
export function setupImagenes() {
    const imageInputs = $('input[name^="IMG_"]');

    imageInputs.each(function () {
        const $input = $(this);
        const rawName = $input.attr('name');
        const fieldName = rawName.replace(/\[\]$/, ''); // Elimina corchetes [] si hay

        $input.off('change.incremental').on('change.incremental', async function (e) {
            if (!e.target.files.length) return;
            const file = e.target.files[0];
            try {
                const url = await comprimirYSubirImagen(file, fieldName, $input);
                if (url) {
                    imagenesSubidas[fieldName] = url;
                    mostrarPreviewImagen($input, url, fieldName);
                }
            } catch (error) {
                // Error ya manejado en comprimirYSubirImagen
            }
        });
    });
}

export { setupImagenes as setupSubidaIncremental };

// Comprimir imagen agresivamente y subirla
async function comprimirYSubirImagen(file, fieldName, $input) {
    subidaEnProceso = true;
    mostrarIndicadorSubida($input, true, fieldName, 'Comprimiendo...');

    try {
        // 1. COMPRIMIR IMAGEN AGRESIVAMENTE
        const imagenComprimida = await comprimirImagenCliente(file);

        if (!imagenComprimida) throw new Error('No se pudo comprimir la imagen');

        const tamañoComprimido = imagenComprimida.size / (1024 * 1024);
        console.log(`📦 Tamaño después de compresión: ${tamañoComprimido.toFixed(2)}MB`);

        // 2. VERIFICAR LÍMITE DE 6MB
        if (tamañoComprimido > 6) throw new Error('La imagen comprimida supera el límite de 6MB');

        // 3. SUBIR AL SERVIDOR
        mostrarIndicadorSubida($input, true, fieldName, 'Subiendo...');
        const url = await subirImagenComprimida(imagenComprimida, fieldName, $input);
        return url;

    } catch (error) {
        console.error('Error en compresión/subida:', error);
        $input.val(''); // Limpiar input en caso de error
    } finally {
        subidaEnProceso = false;
        mostrarIndicadorSubida($input, false, fieldName);
    }
}

// Comprimir imagen en el cliente antes de subir
function comprimirImagenCliente(file) {
    return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = function () {
            const maxW = 900, maxH = 900;
            let w = img.width, h = img.height;
            if (w > maxW || h > maxH) {
                const ratio = Math.min(maxW / w, maxH / h);
                w = Math.round(w * ratio);
                h = Math.round(h * ratio);
            }
            canvas.width = w;
            canvas.height = h;
            ctx.drawImage(img, 0, 0, w, h);
            canvas.toBlob(blob => {
                resolve(blob);
            }, 'image/jpeg', 0.6);
        };
        img.onerror = () => reject(new Error('Error al cargar la imagen'));
        img.src = URL.createObjectURL(file);
    });
}

// Subir imagen ya comprimida al servidor
async function subirImagenComprimida(imagenBlob, fieldName, $input) {
    try {
        const formData = new FormData();
        formData.append('image', imagenBlob, `${fieldName}.jpg`);
        formData.append('field_name', fieldName);

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const response = await fetch('/retail/subir-imagen-incremental', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json'
            },
            body: formData,
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (response.ok && result.success) {
            return result.url;
        } else {
            throw new Error(result.message || 'Error al subir la imagen');
        }
    } catch (error) {
        console.error('Error en subida:', error);
        throw error;
    }
}

// Mostrar indicador de subida mejorado
function mostrarIndicadorSubida($input, show, fieldName, mensaje = 'Subiendo') {
    let $indicator = $input.parent().find('.upload-indicator');

    if (show && $indicator.length === 0) {
        $indicator = $(`
            <div class="upload-indicator" style="
                margin-top: 8px; 
                color: #059669; 
                font-size: 14px; 
                display: flex; 
                align-items: center;
                background: #f0f9ff;
                padding: 8px 12px;
                border-radius: 8px;
                border-left: 4px solid #059669;
            ">
                <div class="spinner" style="
                    width: 16px; height: 16px; 
                    border: 2px solid #d1fae5; 
                    border-top: 2px solid #059669; 
                    border-radius: 50%; 
                    animation: spin 1s linear infinite; 
                    margin-right: 8px;
                "></div>
                🚀 ${mensaje} ${fieldName}...
            </div>
            <style>
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        `);
        $input.after($indicator);
    } else if (show && $indicator.length > 0) {
        $indicator.show().find('div').first().text(`🚀 ${mensaje} ${fieldName}...`);
    } else if (!show && $indicator.length > 0) {
        $indicator.hide();
    }
}

// Mostrar preview mejorado de imagen subida
export function mostrarPreviewImagen($input, url, fieldName) {
    let $preview = $input.parent().find('.image-preview');

    if ($preview.length === 0) {
        $preview = $(`
            <div class="image-preview" style="
                margin-top: 12px;
                padding: 12px;
                background: #f0f9ff;
                border-radius: 8px;
                border: 2px solid #059669;
            ">
                <img src="${url}" alt="${fieldName}" style="
                    max-width: 120px; 
                    max-height: 120px; 
                    border-radius: 8px;
                    display: block;
                    margin: 0 auto 8px auto;
                ">
                <div style="
                    font-size: 12px; 
                    color: #059669; 
                    text-align: center;
                    font-weight: bold;
                ">✅ Imagen guardada en servidor</div>
                <div style="
                    font-size: 11px; 
                    color: #6b7280; 
                    text-align: center;
                    margin-top: 4px;
                ">URL: ${url.substring(0, 40)}...</div>
            </div>
        `);
        $input.after($preview);
    } else {
        $preview.find('img').attr('src', url);
        $preview.find('div:last-child').text(`URL: ${url.substring(0, 40)}...`);
    }
}
