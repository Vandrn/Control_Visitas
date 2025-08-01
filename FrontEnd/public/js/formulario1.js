$(document).ready(function () {

    let dataSaved = false;

    // 🆕 VARIABLES PARA SUBIDA INCREMENTAL
    let imagenesSubidas = {}; // Almacenar URLs de imágenes ya subidas
    let subidaEnProceso = false;

    // 📍 FUNCIÓN PARA CALCULAR DISTANCIA ENTRE DOS COORDENADAS (Haversine)
    /*function calcularDistancia(lat1, lng1, lat2, lng2) {
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
    }*/

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
                    //$('#CRM_ID_TIENDA').off('change.distancia').on('change.distancia', validarDistanciaTienda);
                } else {
                    console.error("La respuesta no es un array:", data);
                }
            });
        }
    });

    // Definir el orden de las vistas
    let secciones = ["intro", "datos", "seccion-1", "intro-2", "seccion-2", "intro-3", "seccion-3", "intro-4", "seccion-4", "intro-5", "seccion-5", "seccion-6", "seccion-7"];
    let indiceActual = 0;

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
                    mostrarNotificacion('⏳ Por favor espere a que termine la subida anterior', 'warning');
                    return;
                }

                // asegúrate de re-declararlo aquí también por seguridad
                const rawName = $input.attr('name');
                const fieldName = rawName.replace(/\[\]$/, '');

                if (fieldName.startsWith('IMG_OBS_')) {
                    for (let i = 0; i < files.length && i < 5; i++) {
                        const file = files[i];

                        if (!file.type.startsWith('image/')) {
                            mostrarNotificacion(`❌ El archivo ${file.name} no es una imagen`, 'error');
                            continue;
                        }

                        if (!imagenesSubidas[fieldName]) {
                            imagenesSubidas[fieldName] = [];
                        }

                        const index = imagenesSubidas[fieldName].length;
                        const indexedFieldName = `${fieldName}_${String(index + 1).padStart(2, '0')}`;

                        const url = await comprimirYSubirImagen(file, indexedFieldName, $input);
                        imagenesSubidas[fieldName].push(url);
                    }

                    return;
                }

                for (let i = 0; i < files.length && i < 5; i++) {
                    const file = files[i];

                    if (!file.type.startsWith('image/')) {
                        mostrarNotificacion(`❌ El archivo ${file.name} no es una imagen`, 'error');
                        continue;
                    }

                    if (!imagenesSubidas[fieldName]) {
                        imagenesSubidas[fieldName] = [];
                    }

                    const index = imagenesSubidas[fieldName].length;
                    const indexedFieldName = `${fieldName}_${String(index + 1).padStart(2, '0')}`;

                    const url = await comprimirYSubirImagen(file, indexedFieldName, $input);
                    imagenesSubidas[fieldName].push(url);
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
            mostrarNotificacion(`❌ ${error.message}`, 'error');
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
                    'X-CSRF-TOKEN': token
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok && result.success) {
                console.log(`✅ Imagen subida: ${fieldName} -> ${result.url}`);
                mostrarNotificacion(`✅ ${fieldName} subida correctamente`, 'success');

                // Mostrar preview
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

    // Recopilar respuestas antes de guardar
    function obtenerEstructuraFinal() {
        transformarValoresRadio(); // Asegúrate de tener los valores transformados

        const seccionesMap = {};
        const kpis = [];
        const planes = [];
        const imagenes = imagenesSubidas || {};
        const mapaCampos = {
            "preg_02_01": "PREG_01_01",
            "preg_02_02": "PREG_01_02",
            "preg_02_03": "PREG_01_03",
            "preg_02_04": "PREG_01_04",
            "preg_02_05": "PREG_01_05",
            "preg_02_06": "PREG_01_06",
            "preg_02_07": "PREG_01_07",
            "preg_02_08": "PREG_01_08",
            "preg_02_09": "PREG_01_09",
            "preg_02_10": "PREG_01_10",
            "preg_02_11": "PREG_01_11",
            "preg_02_12": "PREG_01_12",
            "preg_02_13": "PREG_01_13",
            "preg_02_14": "PREG_01_14",
            "preg_02_15": "PREG_01_15",
            "preg_02_16": "PREG_01_16",
            "preg_02_17": "PREG_01_17",
            "preg_02_18": "PREG_01_18",
            "preg_02_19": "PREG_01_19",
            "preg_02_20": "PREG_01_20",
            "preg_02_21": "PREG_01_21",
            "preg_02_22": "PREG_01_22",
            "obs_02_01": "OBS_01_01",
            "preg_03_01": "PREG_02_01",
            "preg_03_02": "PREG_02_02",
            "preg_03_03": "PREG_02_03",
            "preg_03_04": "PREG_02_04",
            "preg_03_05": "PREG_02_05",
            "preg_03_06": "PREG_02_06",
            "preg_03_07": "PREG_02_08",
            "obs_03_01": "OBS_02_01",
            "preg_04_01": "PREG_03_01",
            "preg_04_02": "PREG_03_02",
            "preg_04_03": "PREG_03_03",
            "preg_04_04": "PREG_03_04",
            "preg_04_05": "PREG_03_05",
            "preg_04_06": "PREG_03_06",
            "preg_04_07": "PREG_03_07",
            "preg_04_08": "PREG_03_08",
            "preg_04_09": "PREG_03_09",
            "obs_04_01": "OBS_03_01",
            "preg_05_01": "PREG_04_02",
            "preg_05_02": "PREG_04_03",
            "preg_05_03": "PREG_04_05",
            "preg_05_04": "PREG_04_06",
            "preg_05_05": "PREG_04_07",
            "preg_05_06": "PREG_04_08",
            "preg_05_07": "PREG_04_09",
            "preg_05_08": "PREG_04_10",
            "preg_05_09": "PREG_04_11",
            "preg_05_10": "PREG_04_12",
            "preg_05_11": "PREG_04_13",
            "preg_05_12": "PREG_04_14",
            "preg_05_13": "PREG_04_15",
            "preg_05_14": "PREG_04_16",
            "obs_05_01": "OBS_04_01",
            "preg_06_01": "PREG_05_01",
            "preg_06_02": "PREG_05_02",
            "preg_06_03": "PREG_05_03",
            "preg_06_04": "PREG_05_04",
            "preg_06_05": "PREG_05_05",
            "preg_06_06": "PREG_05_06",
            "obs_06_01": "OBS_05_01"
        };

        // Recolectar preguntas normales y observaciones
        $("input, select, textarea").not("input[type='file']").each(function () {
            const $el = $(this);
            const rawName = $el.attr("name");
            if (!rawName) return;

            let valor = null;

            if ($el.is(":radio") && $el.is(":checked")) {
                valor = $el.attr("data-transformado") || $el.val();
            } else if (!$el.is(":radio")) {
                valor = $el.val();
            }

            if (!valor || valor.trim() === "") return;

            const codigo = mapaCampos[rawName] || rawName;

            // Detectar sección por código
            const seccionMatch = codigo.match(/^(PREG|OBS)_([0-9]{2})_/);
            if (seccionMatch) {
                const seccionKey = seccionMatch[2]; // ej: '01'
                const seccionNombre = {
                    "01": "operaciones",
                    "02": "administracion",
                    "03": "producto",
                    "04": "personal",
                    "05": "kpi",
                    "06": "otros"
                }[seccionKey] || `seccion_${seccionKey}`;
                if (seccionNombre === "kpi") return; // Evitar duplicar KPIs en secciones
                if (!seccionesMap[seccionNombre]) seccionesMap[seccionNombre] = [];

                // Convertir código de pregunta a código de imagen
                let codigoImg = '';
                if (codigo.startsWith('PREG_')) {
                    // PREG_01_01 → IMG_02_01
                    const partes = codigo.split('_');
                    if (partes.length === 3) {
                        const seccionInterna = String(parseInt(partes[1]) + 1).padStart(2, '0');
                        codigoImg = `IMG_${seccionInterna}_${partes[2]}`;
                    }
                } else if (codigo.startsWith('OBS_')) {
                    const mapeoObs = {
                        'OBS_01_01': 'IMG_OBS_OPE',
                        'OBS_02_01': 'IMG_OBS_ADM',
                        'OBS_03_01': 'IMG_OBS_PRO',
                        'OBS_04_01': 'IMG_OBS_PER',
                    };
                    codigoImg = mapeoObs[codigo] || '';
                }

                const imagenesPregunta = Object.keys(imagenes)
                    .filter(k => k.startsWith(codigoImg))
                    .flatMap(k => imagenes[k] || []);

                seccionesMap[seccionNombre].push({
                    codigo_pregunta: codigo,
                    respuesta: valor,
                    imagenes: imagenesPregunta
                });
            }
        });

        // Recolectar KPIs como bloque separado
        // Recolectar KPIs como bloque separado
        for (let i = 1; i <= 6; i++) {
            const nombreCampo = `preg_06_0${i}`;
            const cod = mapaCampos[nombreCampo] || nombreCampo;
            const val = $(`input[name="${nombreCampo}"]:checked`).val();
            const variacion = $(`input[name="var_06_0${i}"]`).val();

            if (val && variacion !== "") {
                kpis.push({
                    codigo_pregunta: cod,
                    valor: val,
                    variacion: variacion
                });
            }
        }

        // Agregar observación KPI como un KPI especial
        const obsKPI = $(`textarea[name="obs_06_01"]`).val();
        if (obsKPI && obsKPI.trim() !== "") {
            kpis.push({
                codigo_pregunta: "OBS_KPI",
                valor: obsKPI.trim(),
                variacion: ""
            });
        }

        // Recolectar Planes de Acción
        for (let i = 1; i <= 2; i++) {
            const desc = $(`input[name="PLAN_0${i}"]`).val();
            const fecha = $(`input[name="FECHA_PLAN_0${i}"]`).val();
            if (desc && fecha) {
                planes.push({
                    descripcion: desc,
                    fecha_cumplimiento: fecha
                });
            }
        }

        // Recolectar datos generales
        let tienda = $("#CRM_ID_TIENDA option:selected");
        let pais = $("#pais option:selected").data("nombre");
        let datosFinales = {
            session_id: crypto.randomUUID(),
            correo_realizo: $("#correo_tienda").val(),
            lider_zona: $("#jefe_zona").val(),
            tienda: tienda.val() + " - " + tienda.data("ubicacion"),
            ubicacion: $("#ubicacion").val(),
            pais: pais,
            zona: $("#zona").val(),
            fecha_hora_inicio: $("#fecha_inicio").val(),
            fecha_hora_fin: new Date().toISOString(),
            secciones: Object.entries(seccionesMap).map(([nombre, preguntas]) => ({
                nombre_seccion: nombre,
                preguntas: preguntas
            })),
            kpis: kpis,
            planes: planes
        };

        console.log("📦 Estructura final lista para enviar:", datosFinales);
        return datosFinales;
    }

    function guardarSeccion(datos) {
        if (!datos) return;

        // 🆕 ENVÍO SOLO DE DATOS DE TEXTO (SIN ARCHIVOS)
        let formData = new FormData();

        // Solo agregar campos de texto, números y URLs
        for (let key in datos) {
            if (datos[key] !== null && datos[key] !== undefined) {
                // ✅ Las URLs de imágenes ya están en datos[key]
                formData.append(key, datos[key]);
            }
        }

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log('📤 Enviando formulario final (SOLO URLs de imágenes, NO archivos)...');

        // 🚫 MOSTRAR RESUMEN DE IMÁGENES ANTES DE ENVIAR
        const imagenesResumen = Object.keys(imagenesSubidas).length;
        if (imagenesResumen > 0) {
            console.log(`📷 Imágenes subidas previamente: ${imagenesResumen}`);
            Object.entries(imagenesSubidas).forEach(([campo, url]) => {
                console.log(`  ✅ ${campo}: ${url}`);
            });
        }

        fetch('/retail/guardar-seccion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(datos),
        }).then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error("Error al guardar: " + response.status);
            }
        })
            .then(data => {
                console.log("✅ Formulario guardado exitosamente:", data);
                mostrarNotificacion('✅ Formulario enviado correctamente', 'success');

                // 🧹 Limpiar URLs de imágenes de la memoria
                imagenesSubidas = {};
                console.log("🧹 Cache de imágenes limpiado");
            })
            .catch(error => {
                console.error("❌ Error al enviar los datos:", error);
                mostrarNotificacion('❌ Error al enviar el formulario', 'error');
            });
    }

    $(".btnSiguiente").click(function (event) {
        event.preventDefault(); // 🆕 AGREGAR ESTA LÍNEA

        let seccionActual = $("#" + secciones[indiceActual]);
        let inputsVisibles = seccionActual.find("input, select, textarea").filter(function () {
            return $(this).is(":visible") && !$(this).is(":disabled");
        }).toArray();

        if (!$("#correo_tienda").val().includes('@') && indiceActual === secciones.indexOf("datos"))
            return mostrarNotificacion("Ingrese un correo válido.", 'warning');

        // Verificar que no haya subidas en proceso antes de continuar
        if (subidaEnProceso) {
            mostrarNotificacion('⏳ Por favor espere a que termine la subida de la imagen', 'warning');
            return;
        }

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

            guardarSeccion(obtenerEstructuraFinal());
            dataSaved = true;

            mostrarNotificacion(`¡Formulario completado! Se enviaron ${totalImagenes} imágenes correctamente.`, 'success');
            //window.location.replace("/formulario");
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
                let input = $(`input[name='var_06_0${i}']`);
                let valor = input.val();

                // Validar que haya algo y sea número válido
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

        mostrarSeccion(++indiceActual);
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

    // 🆕 Inicializar subida incremental
    setupSubidaIncremental();

    // Mostrar la primera sección al cargar
    mostrarSeccion(indiceActual);

    /**
     * Mostrar notificaciones tipo toast
     */
    function mostrarNotificacion(mensaje, tipo = 'info') {
        const colores = {
            'success': '#059669',
            'error': '#dc2626',
            'warning': '#d97706',
            'info': '#2563eb'
        };

        const iconos = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
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
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            ">
                <span style="margin-right: 8px; font-size: 16px;">${iconos[tipo]}</span>
                <span>${mensaje}</span>
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
            </style>
        `);

        // Agregar al body
        $('body').append($notification);

        // Auto-remover después de 4 segundos
        setTimeout(() => {
            $notification.css({
                'animation': 'slideOut 0.3s ease',
                'animation-fill-mode': 'forwards'
            });

            setTimeout(() => {
                if ($notification.length) {
                    $notification.remove();
                }
            }, 300);
        }, 4000);

        // Permitir cerrar con clic
        $notification.click(function () {
            $(this).css({
                'animation': 'slideOut 0.3s ease',
                'animation-fill-mode': 'forwards'
            });
            setTimeout(() => {
                $(this).remove();
            }, 300);
        });

        return $notification;
    }

    // 🎯 VALIDAR DISTANCIA CUANDO CAMBIA LA TIENDA
    async function validarDistanciaTienda() {
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
        fetch('/keep-alive', {
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