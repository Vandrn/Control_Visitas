$(document).ready(function () {
    // 🆕 FUNCIÓN PARA DETECTAR ERRORES TÉCNICOS
    function esErrorTecnico(mensaje) {
        if (!mensaje) return false;
        
        // Palabras clave que indican errores técnicos/del servidor
        const palabrasClaveTecnicas = [
            'STRUCT', 'JSON', 'type', 'Type', 'undefined', 'Value of type',
            'exception', 'INVALID_ARGUMENT', 'SYNTAX_ERROR',
            'PARSE', 'parsing', 'unexpected', 'Unexpected', 'Cannot',
            'cannot', 'must', 'required', 'constraint', 'foreign key',
            'database', 'BigQuery', 'SQL', 'query', 'parameter', 'token',
            'MERGE', 'INSERT', 'UPDATE', 'DELETE', 'invalidQuery', 'FAILED'
        ];
        
        return palabrasClaveTecnicas.some(palabra => mensaje.includes(palabra));
    }

    // mostrar una pantalla por id
    function mostrarPantalla(id) {
        const pantallas = [
            'intro','datos','seccion-1',
            'intro-2','preguntas-2','seccion-2',
            'intro-3','preguntas-3','seccion-3',
            'intro-4','preguntas-4','seccion-4',
            'intro-5','preguntas-5','seccion-5',
            'seccion-6','seccion-7'
        ];

        pantallas.forEach(pid => {
            const el = document.getElementById(pid);
            if (el) el.style.display = 'none';
        });

        const target = document.getElementById(id);
        if (target) target.style.display = 'block';
    }

    //guardar progreso 
    function getSessionId() {
        return formularioSessionId || sessionStorage.getItem('form_session_id');
    }

    async function guardarProgreso(pantalla) {
        const sessionId = getSessionId();
        if (!sessionId) return;
        await fetch(`/form/progreso/${sessionId}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ pantalla_actual: pantalla })
        });
    }



    // 🆕 FUNCIÓN PARA LIMPIAR MENSAJE DE ERROR
    function obtenerMensajeUsuario(errorMessage, tipo = 'error') {
        console.error('❌ Error capturado:', errorMessage);
        
        if (tipo === 'error' && esErrorTecnico(errorMessage)) {
            console.warn('⚠️ Es error técnico - mostrando mensaje amigable');
            return 'Hubo un problema técnico. Por favor, contacta al administrador.';
        }
        
        // Si no es técnico, devolver el mensaje original
        return errorMessage || 'Ocurrió un error. Por favor, intenta de nuevo.';
    }

    // 🆕 VARIABLE GLOBAL PARA SESSION_ID
    let formularioSessionId = null;
    
    // Modalidad: Virtual o Presencial
    let modalidadSeleccionada = '';
    $(document).on('click', '.modalidad-btn', function() {
        $('.modalidad-btn').removeClass('modalidad-activa');
        $(this).addClass('modalidad-activa');
        modalidadSeleccionada = $(this).data('modalidad');
        $('#modalidad_visita').val(modalidadSeleccionada);
        // Habilitar botón continuar solo si hay correo y modalidad
        checarHabilitarContinuar();
    });

    // Habilitar botón continuar solo si hay correo y modalidad
    function checarHabilitarContinuar() {
        let correoValido = false;
        var sel = $("#correo_tienda_select");
        if (sel.length && sel.val() === 'otro') {
            correoValido = $("#correo_tienda_otro").val().match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/);
        } else if (sel.length) {
            correoValido = !!sel.val();
        } else {
            correoValido = $("#correo_tienda").val().match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/);
        }
    }

    // Validar correo y modalidad al cambiar
    $('#correo_tienda_select, #correo_tienda_otro').on('input change', checarHabilitarContinuar);

    // Estilo para botón activo
    $('<style>.modalidad-activa{background:#e6b200;color:#fff;box-shadow:0 2px 8px #e6b20080;}</style>').appendTo('head');
    // Mostrar/ocultar input de correo 'otro' según selección
    var selectCorreo = document.getElementById('correo_tienda_select');
    var inputCorreoOtro = document.getElementById('correo_tienda_otro');
    if (selectCorreo && inputCorreoOtro) {
        selectCorreo.addEventListener('change', function() {
            if (this.value === 'otro') {
                inputCorreoOtro.style.display = '';
                inputCorreoOtro.required = true;
            } else {
                inputCorreoOtro.style.display = 'none';
                inputCorreoOtro.required = false;
                inputCorreoOtro.value = '';
            }
        });
        // Inicializar estado al cargar
        if (selectCorreo.value === 'otro') {
            inputCorreoOtro.style.display = '';
            inputCorreoOtro.required = true;
        } else {
            inputCorreoOtro.style.display = 'none';
            inputCorreoOtro.required = false;
        }
    }

    let dataSaved = false;

    // 🆕 VARIABLES PARA SUBIDA INCREMENTAL
    let imagenesSubidas = {}; // Almacenar URLs de imágenes ya subidas
    let subidaEnProceso = false;

    // 📍 FUNCIÓN PARA CALCULAR DISTANCIA ENTRE DOS COORDENADAS (Haversine)
    function calcularDistancia(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Radio de la Tierra en metros
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLng / 2) * Math.sin(dLng / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c; // Distancia en metros
    }

    // 📱 OBTENER UBICACIÓN DEL USUARIO
    function obtenerUbicacionUsuario() {
        return new Promise((resolve, reject) => {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // 5 minutos
                });
            } else {
                reject(new Error('Geolocalización no soportada'));
            }
        });
    }

    // Obtener ubicación al cargar la página
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            $("#ubicacion").val(position.coords.latitude + "," + position.coords.longitude);
        }, function (error) {
            console.error("Error obteniendo la ubicación:", error);
        });
    } else {
        console.error("Geolocalización no soportada en este navegador.");
    }

    // Inicializar el campo de fecha con la fecha actual
    $("#fecha_inicio").val(new Date().toISOString());

    // Evento para regresar al inicio al hacer clic en el logo
    $("#logo-regreso").click(function () {
        indiceActual = 0;
        mostrarSeccion(indiceActual);
        location.href = "#intro";
    });

    // Cargar países
    $.get("/retail/paises", function (data) {
        if (Array.isArray(data)) {
            $("#pais").append('<option value="">Seleccione un país</option>');
            data.forEach(p => {
                $("#pais").append(`<option value="${p.value}" data-nombre="${p.label}">${p.label}</option>`);
            });
        } else {
            console.error("La respuesta no es un array:", data);
        }
    });

    // Cargar zonas según el país seleccionado
    $("#pais").change(function () {
        let pais = $(this).val();
        $("#zona").empty().append('<option value="">Seleccione una zona</option>');
        $("#CRM_ID_TIENDA").empty().append('<option value="">Seleccione una tienda</option>');

        if (pais) {
            $.get(`/retail/zonas/${pais}`, function (data) {
                if (Array.isArray(data)) {
                    $("#zona").append(data.map(z => `<option value="${z}">${z}</option>`));
                } else {
                    console.error("La respuesta no es un array:", data);
                }
            });
        }
    });

    // Cargar tiendas según la zona seleccionada
    $("#zona").change(function () {
        let pais = $("#pais").val();
        let zona = $(this).val();
        $("#CRM_ID_TIENDA").empty().append('<option value="">Seleccione una tienda</option>');

        if (pais && zona) {
            $.get(`/retail/tiendas/${pais}/${zona}`, function (data) {
                if (Array.isArray(data)) {
                    data.forEach(t => {
                        $("#CRM_ID_TIENDA").append(
                            `<option value="${t.TIENDA}" data-ubicacion="${t.UBICACION}" data-geo="${t.GEO || ''}">${t.TIENDA}</option>`
                        );
                    });

                    // 📍 AGREGAR EVENTO PARA VALIDAR DISTANCIA
                    $('#CRM_ID_TIENDA').off('change.distancia').on('change.distancia', validarDistanciaTienda);
                } else {
                    console.error("La respuesta no es un array:", data);
                }
            });
        }
    });

    // Definir el orden de las vistas
    let secciones = ["intro", "datos", "seccion-1", "intro-2", "seccion-2", "intro-3", "seccion-3", "intro-4", "seccion-4", "intro-5", "seccion-5", "seccion-6", "seccion-7"];
    let indiceActual = 0;

    // 🆕 SISTEMA DE TRACKING: Qué secciones ya fueron guardadas exitosamente
    const seccionesGuardadas = new Set();

    // 🆕 MAPEO DE NOMBRES REALES DE SECCIONES
    const seccionesMap = {
        'seccion-2': 'Operaciones',
        'seccion-3': 'Administración',
        'seccion-4': 'Producto',
        'seccion-5': 'Personal',
        'seccion-6': 'KPIs',
        'seccion-7': 'Final'
    };

    // 🆕 SECCIONES SIN IMÁGENES
    const seccionesSinImagenes = ['seccion-3', 'seccion-6']; // Admin y KPIs
    
    // 🆕 SECCIONES CON OPCIÓN "NO APLICA"
    const seccionesConNoAplica = ['seccion-4', 'seccion-5']; // Producto y Personal

    function mostrarSeccion(indice) {
        secciones.forEach((seccion, i) => {
            let elemento = $("#" + seccion);
            if (i === indice) {
                elemento.show();
            } else {
                elemento.hide();
            }
        });
    }

    async function restaurarProgreso() {
        const sessionId = getSessionId();
        if (!sessionId) return;

        try {
            const res = await fetch(`/form/progreso/${sessionId}`, { credentials: 'same-origin' });
            const data = await res.json();
            const pantalla = data.pantalla_actual || data.pantallaActual || data.pantalla;
            if (data.success && pantalla) {
                const idx = secciones.indexOf(pantalla);
                if (idx >= 0) indiceActual = idx;
            }
            modalidadSeleccionada = $('#modalidad_visita').val() || modalidadSeleccionada;
        } catch (e) {
            console.warn('No se pudo restaurar progreso', e);
        }
    }

    // Transformar valores de radio buttons (1-5 → 0.2-1)
    function transformarValoresRadio() {
        $("input[type='radio']:checked").each(function () {
            let valor = $(this).val();
            let nuevoValor = { "1": 0.2, "2": 0.4, "3": 0.6, "4": 0.8, "5": 1.0 }[valor] || valor;
            $(this).attr("data-transformado", nuevoValor);
        });
    }

    // ================================
    // 🆕 SUBIDA INCREMENTAL MEJORADA CON COMPRESIÓN
    // ================================

    /**
     * Configurar subida incremental automática con compresión
     */
    function setupSubidaIncremental() {
        const imageInputs = $('input[name^="IMG_"]');

        imageInputs.each(function () {
            const $input = $(this);
            const rawName = $input.attr('name');
            const fieldName = rawName.replace(/\[\]$/, ''); // Elimina corchetes [] si hay

            $input.off('change.incremental').on('change.incremental', async function (e) {
                const files = Array.from(e.target.files);

                if (files.length === 0) return;
                if (subidaEnProceso) {
                    mostrarNotificacion('La imagen anterior se está subiendo. Por favor, espera a que termine.', 'warning');
                    return;
                }

                // asegúrate de re-declararlo aquí también por seguridad
                const rawName = $input.attr('name');
                const fieldName = rawName.replace(/\[\]$/, '');

                if (fieldName.startsWith('IMG_OBS_')) {
                    for (let i = 0; i < files.length && i < 5; i++) {
                        const file = files[i];

                        if (!file.type.startsWith('image/')) {
                            mostrarNotificacion(`El archivo "${file.name}" no es válido. Por favor, sube una imagen (JPG, PNG, etc.).`, 'error');
                            continue;
                        }

                        if (!imagenesSubidas[fieldName]) {
                            imagenesSubidas[fieldName] = [];
                        }

                        const index = imagenesSubidas[fieldName].length;
                        const indexedFieldName = `${fieldName}_${String(index + 1).padStart(2, '0')}`;

                        const url = await comprimirYSubirImagen(file, indexedFieldName, $input);
                        if (url && url.trim() !== '') { // 🆕 SOLO AGREGAR SI ES VÁLIDA
                            imagenesSubidas[fieldName].push(url);
                        }
                    }

                    return;
                }

                for (let i = 0; i < files.length && i < 5; i++) {
                    const file = files[i];

                    if (!file.type.startsWith('image/')) {
                        mostrarNotificacion(`El archivo "${file.name}" no es válido. Por favor, sube una imagen (JPG, PNG, etc.).`, 'error');
                        continue;
                    }

                    if (!imagenesSubidas[fieldName]) {
                        imagenesSubidas[fieldName] = [];
                    }

                    const index = imagenesSubidas[fieldName].length;
                    const indexedFieldName = `${fieldName}_${String(index + 1).padStart(2, '0')}`;

                    const url = await comprimirYSubirImagen(file, indexedFieldName, $input);
                    if (url && url.trim() !== '') { // 🆕 SOLO AGREGAR SI ES VÁLIDA
                        imagenesSubidas[fieldName].push(url);
                    }
                }
            });
        });
    }

    /**
     * Comprimir imagen agresivamente y subirla
     */
    async function comprimirYSubirImagen(file, fieldName, $input) {
        subidaEnProceso = true;
        mostrarIndicadorSubida($input, true, fieldName, 'Comprimiendo...');

        try {
            // 1. COMPRIMIR IMAGEN AGRESIVAMENTE
            const imagenComprimida = await comprimirImagenCliente(file);

            if (!imagenComprimida) {
                throw new Error('Error en la compresión de imagen');
            }

            const tamañoComprimido = imagenComprimida.size / (1024 * 1024);
            console.log(`📦 Tamaño después de compresión: ${tamañoComprimido.toFixed(2)}MB`);

            // 2. VERIFICAR LÍMITE DE 6MB
            if (tamañoComprimido > 6) {
                throw new Error(`Imagen demasiado grande: ${tamañoComprimido.toFixed(2)}MB. Máximo: 6MB`);
            }

            // 3. SUBIR AL SERVIDOR
            mostrarIndicadorSubida($input, true, fieldName, 'Subiendo...');
            const url = await subirImagenComprimida(imagenComprimida, fieldName, $input);
            return url;

        } catch (error) {
            console.error('Error en compresión/subida:', error);
            mostrarNotificacion(`No se pudo subir la imagen. Verifica que el archivo sea válido e intenta de nuevo.`, 'error');
            $input.val(''); // Limpiar input en caso de error
        } finally {
            subidaEnProceso = false;
            mostrarIndicadorSubida($input, false, fieldName);
        }
    }
    /**
     * Comprimir imagen en el cliente antes de subir
     */
    function comprimirImagenCliente(file) {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = function () {
                // Calcular nuevas dimensiones (máximo 1200px)
                const maxWidth = 1200;
                const maxHeight = 1200;

                let { width, height } = img;

                if (width > height) {
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                // Dibujar imagen redimensionada
                ctx.drawImage(img, 0, 0, width, height);

                // Comprimir iterativamente hasta estar bajo 6MB
                let quality = 0.8; // Calidad inicial
                let attempts = 0;
                const maxAttempts = 10;

                function tryCompress() {
                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Error al comprimir imagen'));
                            return;
                        }

                        const sizeInMB = blob.size / (1024 * 1024);
                        console.log(`🔄 Intento ${attempts + 1}: ${sizeInMB.toFixed(2)}MB con calidad ${quality}`);

                        if (sizeInMB <= 6 || attempts >= maxAttempts) {
                            if (sizeInMB <= 6) {
                                console.log(`✅ Compresión exitosa: ${sizeInMB.toFixed(2)}MB`);
                                resolve(blob);
                            } else {
                                reject(new Error(`No se pudo comprimir bajo 6MB después de ${maxAttempts} intentos`));
                            }
                        } else {
                            // Reducir calidad más agresivamente
                            quality *= 0.7;
                            attempts++;
                            tryCompress();
                        }
                    }, 'image/jpeg', quality);
                }

                tryCompress();
            };

            img.onerror = () => reject(new Error('Error al cargar la imagen'));
            img.src = URL.createObjectURL(file);
        });
    }

    /**
     * Subir imagen ya comprimida al servidor
     */
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
          console.log(`✅ Imagen subida: ${fieldName} -> ${result.url}`);
          mostrarNotificacion(`✅ ${fieldName} subida correctamente`, 'success');
          mostrarPreviewImagen($input, result.url, fieldName);
          return result.url;
        } else {
          throw new Error(result.error || 'Error desconocido');
        }
      } catch (error) {
        console.error('Error en subida:', error);
        throw error;
      }
    }

    /**
     * Mostrar indicador de subida mejorado
     */
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
            // Actualizar mensaje
            $indicator.html(`
                <div class="spinner" style="
                    width: 16px; height: 16px; 
                    border: 2px solid #d1fae5; 
                    border-top: 2px solid #059669; 
                    border-radius: 50%; 
                    animation: spin 1s linear infinite; 
                    margin-right: 8px;
                "></div>
                🚀 ${mensaje} ${fieldName}...
            `);
        } else if (!show && $indicator.length > 0) {
            $indicator.remove();
        }
    }

    /**
     * Mostrar preview mejorado de imagen subida
     */
    function mostrarPreviewImagen($input, url, fieldName) {
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

    /**
     * 🆕 GUARDAR "DATOS" (correo, modalidad, ubicación, etc)
     * Se ejecuta en la vista "datos"
     * Retorna: Promise<boolean>
     */
    function guardarDatos() {
        return new Promise((resolve) => {
            // Extraer tienda (opción seleccionada)
            let tiendaVal = '';
            const tiendaSelect = $("#CRM_ID_TIENDA option:selected");
            if (tiendaSelect.length) {
                const val = tiendaSelect.val();
                const ubicacion = tiendaSelect.data("ubicacion") || '';
                tiendaVal = val ? (val + (ubicacion ? " - " + ubicacion : "")) : '';
            }

            // Extraer país
            let paisVal = '';
            const paisSelect = $("#pais option:selected");
            if (paisSelect.length) {
                paisVal = paisSelect.data("nombre") || paisSelect.val() || '';
            }

            // Extraer zona
            let zonaVal = $("#zona").val() || '';

            const datosEnvio = {
                fecha_hora_inicio: $("#fecha_inicio").val(),
                correo_realizo: (function() {
                    var sel = $("#correo_tienda_select");
                    if (sel.length && sel.val() === 'otro') {
                        return $("#correo_tienda_otro").val();
                    } else if (sel.length) {
                        return sel.val();
                    } else {
                        return $("#correo_tienda").val();
                    }
                })(),
                lider_zona: $("#jefe_zona").val(),
                tienda: tiendaVal,
                ubicacion: $("#ubicacion").val(),
                pais: paisVal,
                zona: zonaVal,
                modalidad_visita: $('#modalidad_visita').val()
            };

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            console.log('📤 Guardando DATOS iniciales...', datosEnvio);
            console.log('✅ Valores extraídos:', {
                tienda: tiendaVal,
                pais: paisVal,
                zona: zonaVal
            });

            // Mostrar notificación de cargando
            const notifGuardando = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);

            fetch('/retail/save-datos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(datosEnvio),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.session_id) {
                    formularioSessionId = data.session_id;
                    sessionStorage.setItem('form_session_id', formularioSessionId);
                    guardarProgreso('seccion-1');
                    console.log('✅ Registro inicial creado:', {
                        session_id: formularioSessionId
                    });

                    actualizarNotificacionPermanente(notifGuardando, '✅ Datos guardados correctamente', 'success');
                    setTimeout(() => cerrarNotificacion(notifGuardando), 2000);
                    resolve(true);
                } else {
                    console.error('❌ Error:', data.message);
                    const mensajeUsuario = obtenerMensajeUsuario(data.message);
                    actualizarNotificacionPermanente(notifGuardando, mensajeUsuario, 'error');
                    setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                    resolve(false);
                }
            })
            .catch(error => {
                console.error('❌ Error en guardarDatos:', error);
                actualizarNotificacionPermanente(notifGuardando, 'Error de conexión. Verifica tu internet e intenta de nuevo.', 'error');
                setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                resolve(false);
            });
        });
    }

    /**
     * 🆕 GUARDAR SECCIÓN INDIVIDUAL (seccion-2, seccion-3, etc)
     * ESPECIAL para seccion-1: Solo actualiza pais, zona, tienda (no se guarda como sección)
     * Retorna: Promise<boolean>
     */
    
    // 🆕 VARIABLE PARA GUARDAR DATOS DE SECCION-1
    let datosSeccion1 = {};

    function guardarSeccionActual() {
        return new Promise((resolve) => {
            const nombreSeccionId = secciones[indiceActual]; // ej: "seccion-2"
            
            // 🆕 VERIFICAR SI ESTA SECCIÓN YA FUE GUARDADA
            if ((nombreSeccionId === 'seccion-6' || nombreSeccionId === 'seccion-7') && seccionesGuardadas.has(nombreSeccionId)) {
                console.log(`⏭️ Sección ${nombreSeccionId} ya fue guardada, saltando...`);
                resolve(true);
                return;
            }
            
            // 🆕 ESPECIAL PARA SECCION-1: Solo capturar pais/zona/tienda UNA VEZ
            if (nombreSeccionId === 'seccion-1') {
                console.log('✅ Capturando datos de Seccion-1 para guardar en registro principal');
                
                // Extraer tienda (opción seleccionada)
                let tiendaVal = '';
                const tiendaSelect = $("#CRM_ID_TIENDA option:selected");
                if (tiendaSelect.length) {
                    const val = tiendaSelect.val();
                    const ubicacion = tiendaSelect.data("ubicacion") || '';
                    tiendaVal = val ? (val + (ubicacion ? " - " + ubicacion : "")) : '';
                }

                // Extraer país
                let paisVal = '';
                const paisSelect = $("#pais option:selected");
                if (paisSelect.length) {
                    paisVal = paisSelect.data("nombre") || paisSelect.val() || '';
                }

                // Extraer zona
                let zonaVal = $("#zona").val() || '';

                datosSeccion1 = {
                    pais: paisVal,
                    zona: zonaVal,
                    tienda: tiendaVal
                };

                // 🆕 ACTUALIZAR REGISTRO PRINCIPAL CON ESTOS DATOS
                if (formularioSessionId) {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    const notifGuardando = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);
                    
                    fetch('/retail/save-main-fields', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            session_id: formularioSessionId,
                            main_fields: datosSeccion1
                        }),
                        credentials: 'same-origin'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('✅ Campos principales (pais/zona/tienda) guardados correctamente');
                            actualizarNotificacionPermanente(notifGuardando, '✅ Datos guardados correctamente', 'success');
                            setTimeout(() => cerrarNotificacion(notifGuardando), 2000);
                            resolve(true);
                        } else {
                            console.error('❌ Error:', data.message);
                            const mensajeUsuario = obtenerMensajeUsuario(data.message);
                            actualizarNotificacionPermanente(notifGuardando, mensajeUsuario, 'error');
                            setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                            resolve(false);
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error en save-main-fields:', error);
                        actualizarNotificacionPermanente(notifGuardando, 'Error de conexión. Verifica tu internet e intenta de nuevo.', 'error');
                        setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                        resolve(false);
                    });
                } else {
                    resolve(true);
                }
                return;
            }

            // 🆕 ESPECIAL PARA SECCION-6 (KPIs): Guardar en endpoint diferente
            if (nombreSeccionId === 'seccion-6') {
                console.log('📊 Capturando KPIs desde seccion-6');
                
                if (!formularioSessionId) {
                    formularioSessionId = sessionStorage.getItem('form_session_id');
                }

                if (!formularioSessionId) {
                    mostrarNotificacion('Tu sesión ha expirado. Por favor, recarga la página y comienza de nuevo.', 'error');
                    resolve(false);
                    return;
                }

                // Extraer KPIs: emparejar preg_06_XX con var_06_XX
                const kpis = [];
                for (let i = 1; i <= 6; i++) {
                    const prefijo = String(i).padStart(2, '0');
                    const pregVal = $(`input[name="preg_06_${prefijo}"]:checked`).val();
                    const varVal = $(`input[name="var_06_${prefijo}"]`).val();
                    
                    if (pregVal && varVal && varVal.trim() !== '') {
                        kpis.push({
                            codigo_pregunta: `preg_06_${prefijo}`,
                            valor: pregVal,
                            variacion: varVal
                        });
                    }
                }

                // Agregar observación de KPI si existe
                const obsKPI = $(`textarea[name="obs_06_01"]`).val();
                if (obsKPI && obsKPI.trim() !== '') {
                    kpis.push({
                        codigo_pregunta: 'obs_06_01',
                        valor: obsKPI.trim(),
                        variacion: ''
                    });
                }

                const datosEnvio = {
                    session_id: formularioSessionId,
                    kpis: kpis
                };

                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                console.log('📤 Guardando KPIs:', datosEnvio);

                const notifGuardando = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);

                fetch('/retail/save-kpis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(datosEnvio),
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ KPIs guardados correctamente');
                        seccionesGuardadas.add('seccion-6');
                        sessionStorage.setItem('form_kpis', JSON.stringify(kpis));
                        // 🆕 LIMPIAR VALORES DE KPIs PARA EVITAR DUPLICADOS
                        for (let i = 1; i <= 6; i++) {
                            const prefijo = String(i).padStart(2, '0');
                            $(`input[name="preg_06_${prefijo}"]`).prop('checked', false);
                            $(`input[name="var_06_${prefijo}"]`).val('');
                        }
                        $(`textarea[name="obs_06_01"]`).val('');
                        
                        actualizarNotificacionPermanente(notifGuardando, '✅ KPIs guardados correctamente', 'success');
                        setTimeout(() => cerrarNotificacion(notifGuardando), 2000);
                        resolve(true);
                    } else {
                        console.error('❌ Error:', data.message);
                        const mensajeUsuario = obtenerMensajeUsuario(data.message);
                        actualizarNotificacionPermanente(notifGuardando, mensajeUsuario, 'error');
                        setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                        resolve(false);
                    }
                })
                .catch(error => {
                    console.error('❌ Error en save-kpis:', error);
                    actualizarNotificacionPermanente(notifGuardando, 'Error de conexión. Verifica tu internet e intenta de nuevo.', 'error');
                    setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                    resolve(false);
                });
                return;
            }

            // 🆕 ESPECIAL PARA SECCION-7 (Planes): Guardar TODO de una vez
            if (nombreSeccionId === 'seccion-7') {
                console.log('📋 Guardando Planes, KPIs, Secciones y finalizando');
                
                if (!formularioSessionId) {
                    formularioSessionId = sessionStorage.getItem('form_session_id');
                }

                if (!formularioSessionId) {
                    mostrarNotificacion('Tu sesión ha expirado. Por favor, recarga la página y comienza de nuevo.', 'error');
                    resolve(false);
                    return;
                }

                // Extraer Planes: emparejar PLAN_XX con FECHA_PLAN_XX
                const planes = [];
                for (let i = 1; i <= 3; i++) {
                    const prefijo = String(i).padStart(2, '0');
                    const descVal = $(`input[name="PLAN_${prefijo}"]`).val();
                    const fechaVal = $(`input[name="FECHA_PLAN_${prefijo}"]`).val();
                    
                    if (descVal && fechaVal && descVal.trim() !== '' && fechaVal.trim() !== '') {
                        planes.push({
                            descripcion: descVal.trim(),
                            fecha_cumplimiento: fechaVal.trim()
                        });
                    }
                }

                // 🆕 Obtener secciones y KPIs guardados desde sessionStorage
                const secciones = JSON.parse(sessionStorage.getItem('form_secciones') || '{}');
                const kpis = JSON.parse(sessionStorage.getItem('form_kpis') || '[]');

                const datosEnvio = {
                    session_id: formularioSessionId,
                    planes: planes,
                    secciones: secciones,
                    kpis: kpis
                };

                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                console.log('📤 Guardando TODO (Planes + KPIs + Secciones):', datosEnvio);

                const notifGuardando = mostrarNotificacion('Finalizando formulario...', 'loading', true);

                // 🆕 TODO EN UN SOLO ENDPOINT
                fetch('/retail/save-planes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(datosEnvio),
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Formulario finalizado correctamente (todo en un paso)');
                        seccionesGuardadas.add('seccion-7');
                        
                        // 🆕 LIMPIAR VALORES DE PLANES PARA EVITAR DUPLICADOS
                        for (let i = 1; i <= 3; i++) {
                            const prefijo = String(i).padStart(2, '0');
                            $(`input[name="PLAN_${prefijo}"]`).val('');
                            $(`input[name="FECHA_PLAN_${prefijo}"]`).val('');
                        }
                        
                        actualizarNotificacionPermanente(notifGuardando, '✅ Formulario completado exitosamente', 'success');
                        
                        // 🆕 LIMPIAR STORAGE Y RECARGAR
                        setTimeout(() => {
                            sessionStorage.clear();
                            window.location.href = window.location.href + (window.location.href.indexOf('?') > -1 ? '&' : '?') + 'nocache=' + Date.now();
                        }, 1500);
                        
                        resolve(true);
                    } else {
                        console.error('❌ Error:', data.message);
                        const mensajeUsuario = obtenerMensajeUsuario(data.message);
                        actualizarNotificacionPermanente(notifGuardando, mensajeUsuario, 'error');
                        setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                        resolve(false);
                    }
                })
                .catch(error => {
                    console.error('❌ Error en save-planes:', error);
                    actualizarNotificacionPermanente(notifGuardando, 'Error de conexión. Verifica tu internet e intenta de nuevo.', 'error');
                    setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                    resolve(false);
                });
                return;
            }
            
            if (!formularioSessionId) {
                formularioSessionId = sessionStorage.getItem('form_session_id');
            }

            if (!formularioSessionId) {
                mostrarNotificacion('Tu sesión ha expirado. Por favor, recarga la página y comienza de nuevo.', 'error');
                resolve(false);
                return;
            }

            // 🆕 OBTENER NOMBRE REAL DE LA SECCIÓN
            const nombreSeccionReal = seccionesMap[nombreSeccionId] || nombreSeccionId;

            // Recolectar preguntas de la sección actual
            const seccionElement = $("#" + nombreSeccionId);

            // 🆕 VALIDAR IMÁGENES OBLIGATORIAS (excepto observaciones en todas)
            const imagenesObligatorias = nombreSeccionId === 'seccion-2'; // Solo Operaciones tiene todas obligatorias
            if (imagenesObligatorias) {
                const imagenesFaltantes = [];
                seccionElement.find("input[type='file']").not("input[name='IMG_OBS_OPE']").each(function () {
                    const fieldName = $(this).attr('name').replace(/\[\]$/, '');
                    const imagenesSubidasDelCampo = imagenesSubidas[fieldName] || [];
                    if (imagenesSubidasDelCampo.length === 0) {
                        imagenesFaltantes.push(fieldName);
                    }
                });
                if (imagenesFaltantes.length > 0) {
                    mostrarNotificacion(`Por favor, sube todas las imágenes requeridas para continuar.`, 'warning');
                    resolve(false);
                    return;
                }
            }
            
            // 🆕 VALIDAR IMÁGENES OPCIONALES DE PRODUCTO (solo si NO es "No aplica")
            if (nombreSeccionId === 'seccion-4') {
                const campos = ['preg_04_07', 'preg_04_08']; // Solo Producto
                const imagenesFaltantes = [];
                
                campos.forEach(fieldName => {
                    const valor = $(`input[name="${fieldName}"]:checked`).val();
                    // Solo validar imagen si NO es "No aplica"
                    if (valor && valor !== 'NA' && valor.trim() !== '') {
                        const imagenFieldName = fieldName.replace('preg_', 'IMG_');
                        const imagenesSubidasDelCampo = imagenesSubidas[imagenFieldName] || [];
                        if (imagenesSubidasDelCampo.length === 0) {
                            imagenesFaltantes.push(imagenFieldName);
                        }
                    }
                });
                
                if (imagenesFaltantes.length > 0) {
                    mostrarNotificacion(`Por favor, sube todas las imágenes requeridas para continuar.`, 'warning');
                    resolve(false);
                    return;
                }
            }
            const preguntas = [];

            seccionElement.find("input, select, textarea").not("input[type='file']").each(function () {
                const $el = $(this);
                const rawName = $el.attr("name");
                if (!rawName || rawName.startsWith('IMG_')) return; // Saltar campos de imagen

                let valor = null;

                if ($el.is(":radio") && $el.is(":checked")) {
                    valor = $el.val();
                } else if (!$el.is(":radio")) {
                    valor = $el.val();
                }

                if (!valor || valor.trim() === "") return;

                // 🆕 DIFERENCIAR OBSERVACIONES Y MAPEAR CORRECTAMENTE LA IMAGEN
                let imagenes = [];
                
                if (rawName.startsWith('obs_')) {
                    // Mapeo de observación a nombre de imagen correcto
                    const mapeoObsImagenes = {
                        'obs_02_01': 'IMG_OBS_OPE', // Operaciones
                        'obs_03_01': 'IMG_OBS_ADM', // Administración
                        'obs_04_01': 'IMG_OBS_PRO', // Producto
                        'obs_05_01': 'IMG_OBS_PER'  // Personal
                    };
                    
                    const imagenFieldName = mapeoObsImagenes[rawName] || null;
                    if (imagenFieldName) {
                        // 🆕 GARANTIZAR QUE SIEMPRE SEA UN ARRAY (nunca null o undefined)
                        imagenes = imagenesSubidas[imagenFieldName] ? [...imagenesSubidas[imagenFieldName]] : [];
                    }
                } else if (rawName.startsWith('preg_')) {
                    // Para preguntas normales: preg_02_01 → IMG_02_01
                    const imagenFieldName = rawName.replace('preg_', 'IMG_');
                    // 🆕 GARANTIZAR QUE SIEMPRE SEA UN ARRAY (nunca null o undefined)
                    imagenes = imagenesSubidas[imagenFieldName] ? [...imagenesSubidas[imagenFieldName]] : [];
                }

                preguntas.push({
                    codigo_pregunta: rawName,
                    respuesta: valor,
                    imagenes: imagenes
                });
            });

            const datosEnvio = {
                session_id: formularioSessionId,
                nombre_seccion: nombreSeccionReal, // 🆕 Usar nombre real
                preguntas: preguntas
            };

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            console.log(`📤 Guardando sección: ${nombreSeccionReal}`, datosEnvio);

            const notifGuardando = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);

            fetch('/retail/save-seccion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(datosEnvio),
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    seccionesGuardadas.add(nombreSeccionId);
                    const seccionesGuardadasObj = JSON.parse(sessionStorage.getItem('form_secciones') || '{}');
                    seccionesGuardadasObj[nombreSeccionReal] = preguntas;
                    sessionStorage.setItem('form_secciones', JSON.stringify(seccionesGuardadasObj));
                    console.log(`✅ Sección guardada: ${nombreSeccionReal}`);
                    actualizarNotificacionPermanente(notifGuardando, `✅ ${nombreSeccionReal} guardada correctamente`, 'success');
                    setTimeout(() => cerrarNotificacion(notifGuardando), 2000);
                    resolve(true);
                } else {
                    console.error('❌ Error:', data.message);
                    const mensajeUsuario = obtenerMensajeUsuario(data.message);
                    actualizarNotificacionPermanente(notifGuardando, mensajeUsuario, 'error');
                    setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                    resolve(false);
                }
            })
            .catch(error => {
                console.error('❌ Error en guardarSeccionActual:', error);
                actualizarNotificacionPermanente(notifGuardando, 'Error de conexión. Verifica tu internet e intenta de nuevo.', 'error');
                setTimeout(() => cerrarNotificacion(notifGuardando), 3000);
                resolve(false);
            });
        });
    }

    $(".btnSiguiente").click(function (event) {
        event.preventDefault(); // 🆕 AGREGAR ESTA LÍNEA

        let seccionActual = $("#" + secciones[indiceActual]);
        let inputsVisibles = seccionActual.find("input, select, textarea").filter(function () {
            return $(this).is(":visible") && !$(this).is(":disabled");
        }).toArray();
        
        // Validar que haya modalidad seleccionada
        const idActual = secciones[indiceActual];

        // La modalidad está en "datos", entonces solo se valida ahí
        if (idActual === "datos") {
            if (!$('#modalidad_visita').val() && !modalidadSeleccionada) {
                return mostrarNotificacion("Seleccione la modalidad de la visita.", "warning");
            }
        }

        // Validar correo según el nuevo select/input
        if (indiceActual === secciones.indexOf("datos")) {
            var correo = (function() {
                var sel = $("#correo_tienda_select");
                if (sel.length && sel.val() === 'otro') {
                    return $("#correo_tienda_otro").val();
                } else if (sel.length) {
                    return sel.val();
                } else {
                    return $("#correo_tienda").val();
                }
            })();
            if (!correo || !correo.match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/)) {
                return mostrarNotificacion("Ingrese un correo válido.", 'warning');
            }
        }
        
        // Verificar que no haya subidas en proceso antes de continuar
        if (subidaEnProceso) {
            mostrarNotificacion('⏳ Por favor espere a que termine la subida de la imagen', 'warning');
            return;
        }
        
        // Guardar modalidad en variable global para otras secciones
        window.__modalidad_visita = modalidadSeleccionada;

        // Only save at the very last section
        if (!dataSaved && indiceActual === secciones.length - 1) {
            // Validar al menos un plan completo antes de enviar
            let planesValidos = 0;
            for (let i = 1; i <= 2; i++) {
                const plan = document.querySelector(`input[name="PLAN_0${i}"]`);
                const fecha = document.querySelector(`input[name="FECHA_PLAN_0${i}"]`);
                if (plan && fecha && plan.value.trim() !== '' && fecha.value.trim() !== '') {
                    planesValidos++;
                }
            }

            if (planesValidos < 1) {
                mostrarNotificacion("Debe completar al menos un Plan de Acción y su fecha.", "warning");
                return;
            }

            console.log('🚀 Enviando formulario final con URLs de imágenes...');

            // 🆕 VALIDACIÓN MEJORADA: Verificar que las imágenes requeridas estén subidas
            const imagenesRequeridas = ['IMG_OBS_OPE', 'IMG_OBS_ADM', 'IMG_OBS_PRO', 'IMG_OBS_PER'];
            const imagenesNoSubidas = [];

            imagenesRequeridas.forEach(fieldName => {
                const input = document.querySelector(`input[name='${fieldName}']`);
                if (input && input.files.length > 0) {
                    // Hay archivo seleccionado, verificar si se subió
                    const imagenesAsociadas = Object.keys(imagenesSubidas).filter(k => k.startsWith(fieldName));
                    if (imagenesAsociadas.length === 0) {
                        imagenesNoSubidas.push(fieldName);
                    }
                }
            });

            if (imagenesNoSubidas.length > 0) {
                mostrarNotificacion(`⚠️ Faltan por subir completamente: ${imagenesNoSubidas.join(', ')}`, 'warning');
                console.log(`❌ Imágenes pendientes de subida:`, imagenesNoSubidas);
                return;
            }

            // ✅ Verificar que no hay subidas en proceso
            if (subidaEnProceso) {
                mostrarNotificacion('⏳ Por favor espere a que termine la subida de imágenes', 'warning');
                return;
            }

            // 📊 MOSTRAR RESUMEN FINAL
            const totalImagenes = Object.keys(imagenesSubidas).length;
            console.log(`📷 Total de imágenes subidas: ${totalImagenes}`);

            // 🏁 FINALIZAR FORMULARIO COMPLETO
           guardarSeccionActual().then(success => {
                if (success) {
                    dataSaved = true;
                }
            });
            mostrarNotificacion(`¡Fin del formulario! Se enviaron ${totalImagenes} imágenes correctamente. Espere a que termine de enviarse los datos`, 'success');
            return;
        }

        let hayError = inputsVisibles.some(input => !input.checkValidity());

        if (hayError) {
            mostrarNotificacion('Por favor, complete todos los campos requeridos antes de continuar.', 'warning');
            inputsVisibles.find(input => !input.checkValidity())[0].reportValidity();
            return;
        }

        // Validar campos de variación de KPI si estamos en sección 6
        if (indiceActual === secciones.indexOf("seccion-6")) {
            let variacionesValidas = true;
            for (let i = 1; i <= 6; i++) {
                const prefijo = String(i).padStart(2, '0');
                let input = $(`input[name='var_06_${prefijo}']`);
                let valor = input.val();

                if (valor === "" || isNaN(parseFloat(valor))) {
                    input.addClass('input-error');
                    variacionesValidas = false;
                } else {
                    input.removeClass('input-error');
                }
            }

            if (!variacionesValidas) {
                mostrarNotificacion('Por favor, ingrese valores numéricos válidos en todas las variaciones KPI.', 'warning');
                return;
            }
        }

        const nombreSeccionActual = secciones[indiceActual];

        // 🆕 GUARDAR DATOS SEGÚN SECCIÓN ACTUAL
        if (nombreSeccionActual === "datos") {
            // 📤 PASO 1: Guardar "datos" y obtener session_id
            guardarDatos().then(success => {
                if (success) {
                    const next = secciones[indiceActual + 1];
                    if (next) guardarProgreso(next);
                    mostrarSeccion(++indiceActual);
                }
            });
        } else if (nombreSeccionActual.startsWith("seccion-")) {
            // 📤 PASO 2-7: Guardar sección individual
            guardarSeccionActual().then(success => {
                if (success) {
                    const next = secciones[indiceActual + 1];
                    if (next) guardarProgreso(next);
                    mostrarSeccion(++indiceActual);
                }
            });
        } else {
            // Intros no necesitan guardar, solo avanzar
            const next = secciones[indiceActual + 1];
            if (next) guardarProgreso(next);
            mostrarSeccion(++indiceActual);
        }
    });
    $(".btnEmpezar1").click(function () {
        indiceActual = secciones.indexOf("datos");
        mostrarSeccion(indiceActual);
    });

    $(".btnEmpezar").click(function () {
        let seccion = $(this).data("seccion");
        let introSeccion = `intro-${seccion}`;
        let preguntasSeccion = `preguntas-${seccion}`;

        if ($("#" + introSeccion).length && $("#" + preguntasSeccion).length) {
            $("#" + introSeccion).hide();
            $("#" + preguntasSeccion).show();
            indiceActual = secciones.indexOf("seccion-" + seccion);
            mostrarSeccion(indiceActual);
        }
    });

    setupSubidaIncremental();

    restaurarProgreso().finally(() => {
        mostrarSeccion(indiceActual);
    });

    /**
     * Mostrar notificaciones tipo toast
     */
    function mostrarNotificacion(mensaje, tipo = 'info', permanente = false) {
        const colores = {
            'success': '#059669',
            'error': '#dc2626',
            'warning': '#d97706',
            'info': '#2563eb',
            'loading': '#6366f1'
        };

        const iconos = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️',
            'loading': '⏳'
        };

        const $notification = $(`
            <div class="notification" style="
                position: fixed; 
                top: 20px; 
                right: 20px; 
                z-index: 9999;
                background: ${colores[tipo]}; 
                color: white; 
                padding: 12px 16px; 
                border-radius: 8px;
                font-size: 14px; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-width: 350px; 
                min-width: 250px;
                animation: slideIn 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            ">
                <span style="font-size: 16px; flex-shrink: 0;" class="notif-icon">${iconos[tipo]}</span>
                <span class="notif-message">${mensaje}</span>
            </div>
            <style>
                @keyframes slideIn {
                    from { 
                        transform: translateX(100%); 
                        opacity: 0; 
                    }
                    to { 
                        transform: translateX(0); 
                        opacity: 1; 
                    }
                }
                @keyframes slideOut {
                    from { 
                        transform: translateX(0); 
                        opacity: 1; 
                    }
                    to { 
                        transform: translateX(100%); 
                        opacity: 0; 
                    }
                }
                @keyframes spin {
                    from {
                        transform: rotate(0deg);
                    }
                    to {
                        transform: rotate(360deg);
                    }
                }
                .loading-icon {
                    animation: spin 1s linear infinite !important;
                }
            </style>
        `);

        // Agregar al body
        $('body').append($notification);
        $notification.data('permanente', permanente);

        // Agregar clase de animación si es loading
        if (tipo === 'loading') {
            $notification.find('.notif-icon').addClass('loading-icon');
        }

        // Auto-remover después de 4 segundos (solo si no es permanente)
        if (!permanente) {
            setTimeout(() => {
                cerrarNotificacion($notification);
            }, 4000);
        }

        // Permitir cerrar con clic
        $notification.click(function () {
            cerrarNotificacion($(this));
        });

        return $notification;
    }

    // 🆕 Función auxiliar para cerrar notificaciones
    function cerrarNotificacion($notification) {
        $notification.css({
            'animation': 'slideOut 0.3s ease',
            'animation-fill-mode': 'forwards'
        });
        setTimeout(() => {
            if ($notification.length) {
                $notification.remove();
            }
        }, 300);
    }

    // 🆕 Función para actualizar mensaje de notificación permanente
    function actualizarNotificacionPermanente($notification, nuevoMensaje, nuevoTipo = null) {
        $notification.find('.notif-message').text(nuevoMensaje);
        
        if (nuevoTipo) {
            const colores = {
                'success': '#059669',
                'error': '#dc2626',
                'warning': '#d97706',
                'info': '#2563eb',
                'loading': '#6366f1'
            };
            const iconos = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️',
                'loading': '⏳'
            };
            
            $notification.css('background', colores[nuevoTipo]);
            const $icon = $notification.find('.notif-icon');
            $icon.text(iconos[nuevoTipo]);
            
            // Remover clase de animación si es loading anterior
            $icon.removeClass('loading-icon');
            
            // Agregar clase de animación si es nuevo loading
            if (nuevoTipo === 'loading') {
                $icon.addClass('loading-icon');
            }
        }
    }

    // 🎯 VALIDAR DISTANCIA CUANDO CAMBIA LA TIENDA
    async function validarDistanciaTienda() {
        // Si la modalidad es virtual, no mostrar mensaje de distancia
        if (window.__modalidad_visita === 'virtual') {
            $('#mensaje-distancia').remove();
            return;
        }
        const tiendaSelect = document.getElementById('CRM_ID_TIENDA');
        const selectedOption = tiendaSelect.options[tiendaSelect.selectedIndex];

        // Limpiar mensaje anterior
        $('#mensaje-distancia').remove();

        if (!selectedOption.value || selectedOption.value === '') {
            return; // No hay tienda seleccionada
        }

        // Obtener coordenadas de la tienda del data attribute
        const coordenadasTienda = selectedOption.getAttribute('data-geo');

        if (!coordenadasTienda) {
            mostrarMensajeDistancia('⚠️ No se encontraron coordenadas para esta tienda', 'warning');
            return;
        }

        // Mostrar mensaje de carga
        mostrarMensajeDistancia('📍 Verificando tu ubicación...', 'info');

        try {
            // Obtener ubicación del usuario
            const position = await obtenerUbicacionUsuario();
            const latUsuario = position.coords.latitude;
            const lngUsuario = position.coords.longitude;

            // Parsear coordenadas de la tienda
            const [latTienda, lngTienda] = coordenadasTienda.split(',').map(Number);

            // Calcular distancia
            const distancia = calcularDistancia(latUsuario, lngUsuario, latTienda, lngTienda);
            const distanciaRedondeada = Math.round(distancia);

            // Mostrar resultado con color
            if (distanciaRedondeada <= 50) {
                mostrarMensajeDistancia(
                    `✅ Te encuentras a ${distanciaRedondeada} metros de la tienda`,
                    'success'
                );
            } else {
                mostrarMensajeDistancia(
                    `❌ Te encuentras a ${distanciaRedondeada} metros de la tienda (muy lejos)`,
                    'danger'
                );
            }

        } catch (error) {
            console.error('Error obteniendo ubicación:', error);
            mostrarMensajeDistancia('❌ No se pudo obtener tu ubicación', 'danger');
        }
    }

    // 🎨 MOSTRAR MENSAJE DE DISTANCIA
    function mostrarMensajeDistancia(mensaje, tipo) {
        // Remover mensaje anterior
        $('#mensaje-distancia').remove();

        // Crear nuevo mensaje
        const claseColor = {
            'success': 'background: #10b981; color: white;',
            'danger': 'background: #ef4444; color: white;',
            'warning': 'background: #f59e0b; color: white;',
            'info': 'background: #3b82f6; color: white;'
        }[tipo] || 'background: #6b7280; color: white;';

        const mensajeHtml = `
            <div id="mensaje-distancia" style="
                margin-top: 8px; 
                padding: 12px 16px; 
                border-radius: 8px; 
                font-size: 14px; 
                font-weight: 500;
                text-align: center;
                ${claseColor}
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            ">
                ${mensaje}
            </div>
        `;

        // Insertar después del select de tienda
        $('#CRM_ID_TIENDA').after(mensajeHtml);
    }


    // 🔄 Mantener sesión activa cada 3 minutos con alerta si se pierde
    let intentosFallidosSesion = 0;
    const limiteFallosSesion = 2; // Al segundo fallo consecutivo, muestra alerta
    
    setInterval(() => {
        fetch('/retail/keep-alive', {
            method: 'GET',
            credentials: 'same-origin'
        }).then(response => {
            if (!response.ok) {
                throw new Error(`Código ${response.status}`);
            }
            intentosFallidosSesion = 0; // Reinicia contador si responde bien
            console.log('⏳ Sesión mantenida activa');
        }).catch((err) => {
            intentosFallidosSesion++;
            console.warn(`⚠️ Intento fallido ${intentosFallidosSesion}:`, err);
    
            if (intentosFallidosSesion >= limiteFallosSesion) {
                mostrarNotificacion('⚠️ Tu sesión ha expirado o no se pudo renovar. Por favor recarga la página.', 'warning');
            }
        });
    }, 3 * 60 * 1000); // Cada 3 minutos


});