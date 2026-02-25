// =============================================================
// imagenes.js — Subida incremental, compresión y preview
// Depende de: config.js (imagenesSubidas, subidaEnProceso, getCsrfToken)
//             ui.js     (mostrarNotificacion, mostrarIndicadorSubida, mostrarPreviewImagen)
// =============================================================

function setupSubidaIncremental() {
    $('input[name^="IMG_"]').each(function() {
        var $input    = $(this);
        var fieldName = $input.attr('name').replace(/\[\]$/, '');

        $input.off('change.incremental').on('change.incremental', function(e) {
            var files = Array.from(e.target.files);
            if (files.length === 0) return;

            if (subidaEnProceso) {
                mostrarNotificacion('La imagen anterior se está subiendo. Por favor, espera.', 'warning');
                return;
            }

            var rawName   = $input.attr('name');
            var fName     = rawName.replace(/\[\]$/, '');
            var maxFiles  = 5;

            // Procesar archivos secuencialmente de forma compatible
            function procesarArchivo(index) {
                if (index >= files.length || index >= maxFiles) {
                    return;
                }

                var file = files[index];

                if (!file.type.startsWith('image/')) {
                    mostrarNotificacion('El archivo "' + file.name + '" no es válido. Sube una imagen (JPG, PNG, etc.).', 'error');
                    procesarArchivo(index + 1);
                    return;
                }

                if (!imagenesSubidas[fName]) imagenesSubidas[fName] = [];

                var indexSubida           = imagenesSubidas[fName].length;
                var indexedFieldName = fName + '_' + String(indexSubida + 1).padStart(2, '0');

                // Usar .then() en lugar de await
                comprimirYSubirImagen(file, indexedFieldName, $input)
                    .done(function(url) {
                        if (url && url.trim() !== '') {
                            imagenesSubidas[fName].push(url);
                        }
                        procesarArchivo(index + 1);
                    })
                    .fail(function() {
                        procesarArchivo(index + 1);
                    });
            }

            procesarArchivo(0);
        });
    });
}

function comprimirYSubirImagen(file, fieldName, $input) {
    var deferred = $.Deferred();
    
    subidaEnProceso = true;
    mostrarIndicadorSubida($input, true, fieldName, 'Comprimiendo...');

    comprimirImagenCliente(file)
        .done(function(blob) {
            var sizeMB = blob.size / (1024 * 1024);
            if (sizeMB > 6) {
                mostrarNotificacion('Imagen demasiado grande: ' + sizeMB.toFixed(2) + 'MB. Máximo: 6MB', 'error');
                $input.val('');
                subidaEnProceso = false;
                mostrarIndicadorSubida($input, false, fieldName);
                deferred.reject(new Error('Imagen demasiado grande'));
                return;
            }

            mostrarIndicadorSubida($input, true, fieldName, 'Subiendo...');
            subirImagenComprimida(blob, fieldName, $input)
                .done(function(url) {
                    deferred.resolve(url);
                })
                .fail(function(err) {
                    console.error('Error en subida:', err);
                    mostrarNotificacion('No se pudo subir la imagen. Verifica que el archivo sea válido e intenta de nuevo.', 'error');
                    $input.val('');
                    deferred.reject(err);
                })
                .always(function() {
                    subidaEnProceso = false;
                    mostrarIndicadorSubida($input, false, fieldName);
                });
        })
        .fail(function(err) {
            console.error('Error en compresión:', err);
            mostrarNotificacion('No se pudo procesar la imagen. Verifica que el archivo sea válido.', 'error');
            $input.val('');
            subidaEnProceso = false;
            mostrarIndicadorSubida($input, false, fieldName);
            deferred.reject(err);
        });

    return deferred.promise();
}

function comprimirImagenCliente(file) {
    var deferred = $.Deferred();
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
                if (!blob) { 
                    deferred.reject(new Error('Error al comprimir imagen')); 
                    return; 
                }
                var mb = blob.size / (1024 * 1024);
                if (mb <= 6 || attempts >= maxAttempts) {
                    if (mb <= 6) {
                        deferred.resolve(blob);
                    } else {
                        deferred.reject(new Error('No se pudo comprimir bajo 6MB'));
                    }
                } else {
                    quality  *= 0.7;
                    attempts++;
                    tryCompress();
                }
            }, 'image/jpeg', quality);
        }

        tryCompress();
    };

    img.onerror = function() { 
        deferred.reject(new Error('Error al cargar la imagen')); 
    };
    
    img.src = URL.createObjectURL(file);
    return deferred.promise();
}

function subirImagenComprimida(blob, fieldName, $input) {
    var deferred = $.Deferred();
    var formData = new FormData();
    formData.append('image', blob, fieldName + '.jpg');
    formData.append('field_name', fieldName);

    $.ajax({
        url: '/retail/subir-imagen-incremental',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': getCsrfToken(), 'Accept': 'application/json' },
        data: formData,
        processData: false,
        contentType: false,
        timeout: 60000, // 60 segundos para subida
        xhrFields: { withCredentials: true },
        success: function(result) {
            if (result.success) {
                mostrarNotificacion(fieldName + ' subida correctamente', 'success');
                mostrarPreviewImagen($input, result.url, fieldName);
                deferred.resolve(result.url);
            } else {
                deferred.reject(new Error(result.error || 'Error desconocido al subir imagen'));
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('Error AJAX:', textStatus, errorThrown);
            deferred.reject(new Error('Error de conexión al subir imagen'));
        }
    });

    return deferred.promise();
}
