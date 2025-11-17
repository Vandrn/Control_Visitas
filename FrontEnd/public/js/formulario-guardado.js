// Guardado del formulario

// Recolectar respuestas y estructura final del formulario
// Recolectar respuestas y estructura final del formulario
export function obtenerEstructuraFinal(imagenesSubidas) {
    // Transformar valores radio
    $("input[type='radio']:checked").each(function () {
        let valor = $(this).val();
        let nuevoValor = { "1": 0.2, "2": 0.4, "3": 0.6, "4": 0.8, "5": 1.0 }[valor] || valor;
        $(this).attr("data-transformado", nuevoValor);
    });

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
        } else {
            valor = $el.val();
        }

        if (!valor || valor.trim() === "") return;

        const codigo = mapaCampos[rawName] || rawName;

        // Detectar sección por código
        const seccionMatch = codigo.match(/^(PREG|OBS)_([0-9]{2})_/);
        if (seccionMatch) {
            const tipo = seccionMatch[1];
            const num = seccionMatch[2];
            const nombreSeccion = `${tipo}_${num}`;
            if (!seccionesMap[nombreSeccion]) seccionesMap[nombreSeccion] = [];
            seccionesMap[nombreSeccion].push({
                codigo_pregunta: codigo,
                valor: valor
            });
        }
    });

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
    // Recolectar plan adicional opcional
    const descAdicional = $(`input[name="PLAN_03"]`).val();
    const fechaAdicional = $(`input[name="FECHA_PLAN_03"]`).val();
    if (descAdicional && fechaAdicional) {
        planes.push({
            descripcion: descAdicional,
            fecha_cumplimiento: fechaAdicional
        });
    }

    // Recolectar datos generales
    let tienda = $("#CRM_ID_TIENDA option:selected");
    let pais = $("#pais option:selected").data("nombre");
    let datosFinales = {
        session_id: crypto.randomUUID(),
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
        tienda: tienda.val() + " - " + tienda.data("ubicacion"),
        ubicacion: $("#ubicacion").val(),
        pais: pais,
        zona: $("#zona").val(),
        fecha_hora_inicio: $("#fecha_inicio").val(),
        fecha_hora_fin: new Date().toISOString(),
        modalidad_visita: $('#modalidad_visita').val(),
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

// Guardar la sección en el backend
// Guardar la sección en el backend
export function guardarSeccion(datos, imagenesSubidas) {
    if (!datos) return;

    // Mostrar el JSON final que se enviará al backend
    console.log('📦 JSON final a enviar al backend:', JSON.stringify(datos, null, 2));

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // 🚫 MOSTRAR RESUMEN DE IMÁGENES ANTES DE ENVIAR
    const imagenesResumen = Object.keys(imagenesSubidas).length;
    if (imagenesResumen > 0) {
        console.log(`📷 Imágenes subidas previamente: ${imagenesResumen}`);
        Object.entries(imagenesSubidas).forEach(([campo, url]) => {
            console.log(`Campo: ${campo}, URL: ${url}`);
        });
    }

    fetch('/retail/guardar-seccion', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
      },
      body: JSON.stringify(datos),
      credentials: 'same-origin'
    }).then(async response => {
        if (response.ok) {
            return response.json();
        } else {
            const error = await response.json();
            throw new Error(error.message || 'Error al guardar la sección');
        }
    })
        .then(data => {
            console.log("✅ Formulario guardado exitosamente:", data);
            // Aquí podrías llamar a mostrarNotificacion si lo importas
            // mostrarNotificacion('✅ Formulario enviado correctamente', 'success');

            // 🧹 Limpiar URLs de imágenes de la memoria
            // imagenesSubidas = {};
            console.log("🧹 Cache de imágenes limpiado");
            // Limpiar estado guardado localmente al completar exitosamente
            if (window.formStorage) window.formStorage.clearState();
            // Borrar cookie usada para mostrar sección en primer render
            try {
                document.cookie = 'cv_form_idx=; path=/; max-age=0;';
            } catch(e){}
            // Redirigir a la pantalla principal
            setTimeout(function() {
                window.location.replace("/retail/formulario");
            }, 1200);
        })
        .catch(error => {
            console.error("❌ Error al enviar los datos:", error);
            // mostrarNotificacion('❌ ' + (error.message || 'Error al enviar el formulario'), 'error');
        });
}

// Setup para el guardado (puede incluir binds de eventos si es necesario)
export function setupGuardado() {
    // Aquí puedes inicializar binds de eventos para el guardado si lo necesitas
}
