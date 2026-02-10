/**
 * 🆕 NUEVAS FUNCIONES PARA GUARDAR POR SECCIÓN
 * Agregar estas funciones dentro del $(document).ready() de formulario1.js
 */

/**
 * 🆕 GUARDAR "DATOS" (correo, modalidad, ubicación, etc)
 * Se ejecuta en la vista "datos"
 * Retorna: Promise<boolean>
 */
function guardarDatos() {
    return new Promise((resolve) => {
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
            tienda: $("#CRM_ID_TIENDA option:selected").val() + " - " + $("#CRM_ID_TIENDA option:selected").data("ubicacion"),
            ubicacion: $("#ubicacion").val(),
            pais: $("#pais option:selected").data("nombre"),
            zona: $("#zona").val(),
            modalidad_visita: $('#modalidad_visita').val()
        };

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log('📤 Guardando DATOS iniciales...', datosEnvio);

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
                window.formularioSessionId = data.session_id;
                sessionStorage.setItem('form_session_id', window.formularioSessionId);
                
                console.log('✅ Registro inicial creado:', {
                    session_id: window.formularioSessionId
                });

                mostrarNotificacion('✅ Datos guardados correctamente', 'success');
                resolve(true);
            } else {
                console.error('❌ Error:', data.message);
                mostrarNotificacion('❌ ' + (data.message || 'Error al guardar datos'), 'error');
                resolve(false);
            }
        })
        .catch(error => {
            console.error('❌ Error en guardarDatos:', error);
            mostrarNotificacion('❌ Error de conexión', 'error');
            resolve(false);
        });
    });
}

/**
 * 🆕 GUARDAR SECCIÓN INDIVIDUAL (seccion-1, seccion-2, etc)
 * Retorna: Promise<boolean>
 */
function guardarSeccionActual() {
    return new Promise((resolve) => {
        const nombreSeccion = window.secciones[window.indiceActual]; // ej: "seccion-1"
        
        // Obtener session_id
        if (!window.formularioSessionId) {
            window.formularioSessionId = sessionStorage.getItem('form_session_id');
        }

        if (!window.formularioSessionId) {
            mostrarNotificacion('❌ Sesión no iniciada. Por favor comience desde el inicio.', 'error');
            resolve(false);
            return;
        }

        // Recolectar preguntas de la sección actual
        const seccionElement = $("#" + nombreSeccion);
        const preguntas = [];

        seccionElement.find("input, select, textarea").not("input[type='file']").each(function () {
            const $el = $(this);
            const rawName = $el.attr("name");
            if (!rawName) return;

            let valor = null;

            if ($el.is(":radio") && $el.is(":checked")) {
                valor = $el.val();
            } else if (!$el.is(":radio")) {
                valor = $el.val();
            }

            if (!valor || valor.trim() === "") return;

            preguntas.push({
                codigo_pregunta: rawName,
                valor: valor,
                imagenes: []
            });
        });

        const datosEnvio = {
            session_id: window.formularioSessionId,
            nombre_seccion: nombreSeccion,
            preguntas: preguntas
        };

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        console.log(`📤 Guardando sección: ${nombreSeccion}`, datosEnvio);

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
                console.log(`✅ Sección guardada: ${nombreSeccion}`);
                mostrarNotificacion(`✅ Sección guardada correctamente`, 'success');
                resolve(true);
            } else {
                console.error('❌ Error:', data.message);
                mostrarNotificacion('❌ ' + (data.message || 'Error al guardar sección'), 'error');
                resolve(false);
            }
        })
        .catch(error => {
            console.error('❌ Error en guardarSeccionActual:', error);
            mostrarNotificacion('❌ Error de conexión', 'error');
            resolve(false);
        });
    });
}

/**
 * 🆕 FINALIZAR FORMULARIO (KPIs y Planes finales)
 */
function finalizarFormularioCompleto() {
    if (!window.formularioSessionId) {
        window.formularioSessionId = sessionStorage.getItem('form_session_id');
    }

    if (!window.formularioSessionId) {
        mostrarNotificacion('❌ Sesión no iniciada', 'error');
        return;
    }

    const kpis = [];
    const planes = [];

    // Recolectar KPIs
    for (let i = 1; i <= 6; i++) {
        const val = $(`input[name="preg_06_0${i}"]:checked`).val();
        const variacion = $(`input[name="var_06_0${i}"]`).val();
        if (val && variacion !== "") {
            kpis.push({
                codigo_pregunta: `PREG_05_0${i}`,
                valor: val,
                variacion: variacion
            });
        }
    }

    // Recolectar Planes
    for (let i = 1; i <= 3; i++) {
        const desc = $(`input[name="PLAN_0${i}"]`).val();
        const fecha = $(`input[name="FECHA_PLAN_0${i}"]`).val();
        if (desc && fecha) {
            planes.push({
                descripcion: desc,
                fecha_cumplimiento: fecha
            });
        }
    }

    const datosFinales = {
        session_id: window.formularioSessionId,
        kpis: kpis,
        planes: planes,
        secciones: []
    };

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    console.log('🏁 Finalizando formulario...', datosFinales);

    fetch('/retail/finalizar-formulario', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify(datosFinales),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Formulario completado:', data);
            mostrarNotificacion('✅ ¡Formulario enviado exitosamente!', 'success');
            
            // Limpiar sesión
            sessionStorage.removeItem('form_session_id');
            window.formularioSessionId = null;
            window.imagenesSubidas = {};
        } else {
            console.error('❌ Error:', data.message);
            mostrarNotificacion('❌ ' + (data.message || 'Error al finalizar'), 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error en finalizarFormularioCompleto:', error);
        mostrarNotificacion('❌ Error de conexión', 'error');
    });
}
