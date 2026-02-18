// =============================================================
// imagenes.js — Subida incremental, compresión y preview
// Depende de: config.js (imagenesSubidas, subidaEnProceso, getCsrfToken)
//             ui.js     (mostrarNotificacion, mostrarIndicadorSubida, mostrarPreviewImagen)
// =============================================================

function setupSubidaIncremental() {
    $('input[name^="IMG_"]').each(function() {
        var $input    = $(this);
        var fieldName = $input.attr('name').replace(/\[\]$/, '');

        $input.off('change.incremental').on('change.incremental', async function(e) {
            var files = Array.from(e.target.files);
            if (files.length === 0) return;

            if (subidaEnProceso) {
                mostrarNotificacion('La imagen anterior se está subiendo. Por favor, espera.', 'warning');
                return;
            }

            var rawName   = $input.attr('name');
            var fName     = rawName.replace(/\[\]$/, '');
            var maxFiles  = 5;

            for (var i = 0; i < files.length && i < maxFiles; i++) {
                var file = files[i];

                if (!file.type.startsWith('image/')) {
                    mostrarNotificacion('El archivo "' + file.name + '" no es válido. Sube una imagen (JPG, PNG, etc.).', 'error');
                    continue;
                }

                if (!imagenesSubidas[fName]) imagenesSubidas[fName] = [];

                var index           = imagenesSubidas[fName].length;
                var indexedFieldName = fName + '_' + String(index + 1).padStart(2, '0');

                var url = await comprimirYSubirImagen(file, indexedFieldName, $input);
                if (url && url.trim() !== '') {
                    imagenesSubidas[fName].push(url);
                }
            }
        });
    });
}

async function comprimirYSubirImagen(file, fieldName, $input) {
    subidaEnProceso = true;
    mostrarIndicadorSubida($input, true, fieldName, 'Comprimiendo...');

    try {
        var blob = await comprimirImagenCliente(file);

        if (!blob) throw new Error('Error en la compresión de imagen');

        var sizeMB = blob.size / (1024 * 1024);
        if (sizeMB > 6) throw new Error('Imagen demasiado grande: ' + sizeMB.toFixed(2) + 'MB. Máximo: 6MB');

        mostrarIndicadorSubida($input, true, fieldName, 'Subiendo...');
        return await subirImagenComprimida(blob, fieldName, $input);

    } catch (err) {
        console.error('Error en compresión/subida:', err);
        mostrarNotificacion('No se pudo subir la imagen. Verifica que el archivo sea válido e intenta de nuevo.', 'error');
        $input.val('');
    } finally {
        subidaEnProceso = false;
        mostrarIndicadorSubida($input, false, fieldName);
    }
}

function comprimirImagenCliente(file) {
    return new Promise(function(resolve, reject) {
        var canvas = document.createElement('canvas');
        var ctx    = canvas.getContext('2d');
        var img    = new Image();

        img.onload = function() {
            var MAX   = 1200;
            var w     = img.width;
            var h     = img.height;

            if (w > h) {
                if (w > MAX) { h = (h * MAX) / w; w = MAX; }
            } else {
                if (h > MAX) { w = (w * MAX) / h; h = MAX; }
            }

            canvas.width  = w;
            canvas.height = h;
            ctx.drawImage(img, 0, 0, w, h);

            var quality     = 0.8;
            var attempts    = 0;
            var maxAttempts = 10;

            function tryCompress() {
                canvas.toBlob(function(blob) {
                    if (!blob) { reject(new Error('Error al comprimir imagen')); return; }
                    var mb = blob.size / (1024 * 1024);
                    if (mb <= 6 || attempts >= maxAttempts) {
                        mb <= 6 ? resolve(blob) : reject(new Error('No se pudo comprimir bajo 6MB'));
                    } else {
                        quality  *= 0.7;
                        attempts++;
                        tryCompress();
                    }
                }, 'image/jpeg', quality);
            }

            tryCompress();
        };

        img.onerror = function() { reject(new Error('Error al cargar la imagen')); };
        img.src     = URL.createObjectURL(file);
    });
}

async function subirImagenComprimida(blob, fieldName, $input) {
    var formData = new FormData();
    formData.append('image', blob, fieldName + '.jpg');
    formData.append('field_name', fieldName);

    var response = await fetch('/retail/subir-imagen-incremental', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        body: formData,
        credentials: 'same-origin'
    });

    var result = await response.json();

    if (response.ok && result.success) {
        mostrarNotificacion(fieldName + ' subida correctamente', 'success');
        mostrarPreviewImagen($input, result.url, fieldName);
        return result.url;
    }

    throw new Error(result.error || 'Error desconocido al subir imagen');
}
