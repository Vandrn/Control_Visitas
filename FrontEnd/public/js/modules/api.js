// =============================================================
// api.js — Comunicación con el servidor (fetch/AJAX)
// Depende de: config.js, ui.js
// =============================================================

// --- Progreso de sesión ---

async function guardarProgreso(pantalla) {
    var sid = getSessionId();
    if (!sid) return;
    await fetch('/retail/form/progreso/' + sid, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
        body: JSON.stringify({ pantalla_actual: pantalla })
    });
}

async function restaurarProgreso() {
    var sid = getSessionId();
    if (!sid) return;
    try {
        var res  = await fetch('/retail/form/progreso/' + sid, { credentials: 'same-origin' });
        var data = await res.json();
        var pantalla = data.pantalla_actual || data.pantallaActual || data.pantalla;
        if (data.success && pantalla) {
            var idx = SECCIONES.indexOf(pantalla);
            if (idx >= 0) indiceActual = idx;
        }
        modalidadSeleccionada = $('#modalidad_visita').val() || modalidadSeleccionada;
    } catch (e) {
        console.warn('No se pudo restaurar progreso', e);
    }
}

// --- Helpers internos ---

function _fetchPost(url, payload, notifGuardando) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload),
        credentials: 'same-origin'
    }).then(function(r) { return r.json(); });
}

function _onSuccess(notif, msg, resolve) {
    actualizarNotificacionPermanente(notif, msg, 'success');
    setTimeout(function() { cerrarNotificacion(notif); }, 2000);
    resolve(true);
}

function _onError(notif, msg, resolve) {
    var msgUsuario = obtenerMensajeUsuario(msg);
    actualizarNotificacionPermanente(notif, msgUsuario, 'error');
    setTimeout(function() { cerrarNotificacion(notif); }, 3000);
    resolve(false);
}

function _onConnectionError(notif, resolve) {
    actualizarNotificacionPermanente(notif, 'Error de conexión. Verifica tu internet e intenta de nuevo.', 'error');
    setTimeout(function() { cerrarNotificacion(notif); }, 3000);
    resolve(false);
}

// --- Guardar datos iniciales ---

function guardarDatos() {
    return new Promise(function(resolve) {
        var tiendaSelect = $("#CRM_ID_TIENDA option:selected");
        var tiendaVal    = '';
        if (tiendaSelect.length) {
            var v   = tiendaSelect.val();
            var ubi = tiendaSelect.data("ubicacion") || '';
            tiendaVal = v ? (v + (ubi ? ' - ' + ubi : '')) : '';
        }

        var paisSelect = $("#pais option:selected");
        var paisVal    = paisSelect.length ? (paisSelect.data("nombre") || paisSelect.val() || '') : '';
        var zonaVal    = $("#zona").val() || '';

        var correo = (function() {
            var sel = $("#correo_tienda_select");
            if (sel.length && sel.val() === 'otro') return $("#correo_tienda_otro").val();
            if (sel.length) return sel.val();
            return $("#correo_tienda").val();
        })();

        var payload = {
            fecha_hora_inicio:  $("#fecha_inicio").val(),
            correo_realizo:     correo,
            lider_zona:         $("#jefe_zona").val(),
            tienda:             tiendaVal,
            ubicacion:          $("#ubicacion").val(),
            pais:               paisVal,
            zona:               zonaVal,
            modalidad_visita:   $('#modalidad_visita').val()
        };

        console.log('📤 [DATOS] Enviando datos iniciales:', payload);

        var notif = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);

        _fetchPost('/retail/save-datos', payload)
            .then(function(data) {
                if (data.success && data.session_id) {
                    formularioSessionId = data.session_id;
                    sessionStorage.setItem('form_session_id', formularioSessionId);
                    guardarProgreso('seccion-1');
                    console.log('✅ [DATOS] Guardados correctamente. session_id:', formularioSessionId);
                    _onSuccess(notif, 'Datos guardados correctamente', resolve);
                } else {
                    console.error('❌ [DATOS] Error al guardar:', data.message);
                    _onError(notif, data.message, resolve);
                }
            })
            .catch(function(e) { console.error('❌ [DATOS] Error de conexión:', e); _onConnectionError(notif, resolve); });
    });
}

// --- Guardar sección actual ---

function guardarSeccionActual() {
    return new Promise(function(resolve) {
        var seccionId = SECCIONES[indiceActual];

        // Secciones que no se guardan dos veces
        if ((seccionId === 'seccion-6' || seccionId === 'seccion-7') && seccionesGuardadas.has(seccionId)) {
            resolve(true);
            return;
        }

        // Seccion-1: actualizar pais/zona/tienda en registro principal
        if (seccionId === 'seccion-1') {
            _guardarSeccion1(resolve);
            return;
        }

        // Seccion-6: KPIs
        if (seccionId === 'seccion-6') {
            _guardarKPIs(resolve);
            return;
        }

        // Seccion-7: Planes (finalización)
        if (seccionId === 'seccion-7') {
            _guardarPlanes(resolve);
            return;
        }

        // Secciones normales
        _guardarSeccionGenerica(seccionId, resolve);
    });
}

function _guardarSeccion1(resolve) {
    var tiendaSelect = $("#CRM_ID_TIENDA option:selected");
    var tiendaVal    = '';
    if (tiendaSelect.length) {
        var v = tiendaSelect.val();
        var u = tiendaSelect.data("ubicacion") || '';
        tiendaVal = v ? (v + (u ? ' - ' + u : '')) : '';
    }
    var paisSelect = $("#pais option:selected");
    var paisVal    = paisSelect.length ? (paisSelect.data("nombre") || paisSelect.val() || '') : '';
    datosSeccion1  = { pais: paisVal, zona: $("#zona").val() || '', tienda: tiendaVal };

    console.log('📤 [SECCION-1] Enviando pais/zona/tienda:', datosSeccion1);

    if (!formularioSessionId) { resolve(true); return; }

    var notif = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);
    _fetchPost('/retail/save-main-fields', { session_id: formularioSessionId, main_fields: datosSeccion1 })
        .then(function(data) {
            if (data.success) {
                console.log('✅ [SECCION-1] Campos principales guardados:', datosSeccion1);
                _onSuccess(notif, 'Datos guardados correctamente', resolve);
            } else {
                console.error('❌ [SECCION-1] Error:', data.message);
                _onError(notif, data.message, resolve);
            }
        })
        .catch(function(e) { console.error('❌ [SECCION-1] Error de conexión:', e); _onConnectionError(notif, resolve); });
}

function _guardarKPIs(resolve) {
    if (!formularioSessionId) formularioSessionId = sessionStorage.getItem('form_session_id');
    if (!formularioSessionId) {
        mostrarNotificacion('Tu sesión ha expirado. Por favor, recarga la página y comienza de nuevo.', 'error');
        resolve(false); return;
    }

    var kpis = [];
    for (var i = 1; i <= 6; i++) {
        var pre = String(i).padStart(2, '0');
        var pv  = $('input[name="preg_06_' + pre + '"]:checked').val();
        var vv  = $('input[name="var_06_' + pre + '"]').val();
        if (pv && vv && vv.trim() !== '') {
            kpis.push({ codigo_pregunta: 'preg_06_' + pre, valor: pv, variacion: vv });
        }
    }
    var obs = $('textarea[name="obs_06_01"]').val();
    if (obs && obs.trim() !== '') {
        kpis.push({ codigo_pregunta: 'obs_06_01', valor: obs.trim(), variacion: '' });
    }

    console.log('📤 [KPIs] Enviando KPIs (' + kpis.length + ' items):', kpis);

    var notif = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);
    _fetchPost('/retail/save-kpis', { session_id: formularioSessionId, kpis: kpis })
        .then(function(data) {
            if (data.success) {
                seccionesGuardadas.add('seccion-6');
                sessionStorage.setItem('form_kpis', JSON.stringify(kpis));
                for (var j = 1; j <= 6; j++) {
                    var p2 = String(j).padStart(2, '0');
                    $('input[name="preg_06_' + p2 + '"]').prop('checked', false);
                    $('input[name="var_06_' + p2 + '"]').val('');
                }
                $('textarea[name="obs_06_01"]').val('');
                console.log('✅ [KPIs] Guardados correctamente:', kpis);
                _onSuccess(notif, 'KPIs guardados correctamente', resolve);
            } else {
                console.error('❌ [KPIs] Error:', data.message);
                _onError(notif, data.message, resolve);
            }
        })
        .catch(function(e) { console.error('❌ [KPIs] Error de conexión:', e); _onConnectionError(notif, resolve); });
}

function _guardarPlanes(resolve) {
    if (!formularioSessionId) formularioSessionId = sessionStorage.getItem('form_session_id');
    if (!formularioSessionId) {
        mostrarNotificacion('Tu sesión ha expirado. Por favor, recarga la página y comienza de nuevo.', 'error');
        resolve(false); return;
    }

    var planes = [];
    for (var i = 1; i <= 3; i++) {
        var pre  = String(i).padStart(2, '0');
        var desc = $('input[name="PLAN_' + pre + '"]').val();
        var fec  = $('input[name="FECHA_PLAN_' + pre + '"]').val();
        if (desc && fec && desc.trim() !== '' && fec.trim() !== '') {
            planes.push({ descripcion: desc.trim(), fecha_cumplimiento: fec.trim() });
        }
    }

    var seccionesAcumuladas = JSON.parse(sessionStorage.getItem('form_secciones') || '{}');
    var kpisAcumulados      = JSON.parse(sessionStorage.getItem('form_kpis') || '[]');

    var payload = {
        session_id: formularioSessionId,
        planes:     planes,
        secciones:  seccionesAcumuladas,
        kpis:       kpisAcumulados
    };

    console.log('📤 [PLANES] Enviando planes:', planes);
    console.groupCollapsed('📋 [RESUMEN COMPLETO DE LA VISITA] session_id: ' + formularioSessionId);
    console.log('Secciones acumuladas:', seccionesAcumuladas);
    console.log('KPIs acumulados:', kpisAcumulados);
    console.log('Planes de acción:', planes);
    console.log('Payload completo enviado a /save-planes:', payload);
    console.groupEnd();

    var notif = mostrarNotificacion('Finalizando formulario...', 'loading', true);
    _fetchPost('/retail/save-planes', payload)
        .then(function(data) {
            if (data.success) {
                seccionesGuardadas.add('seccion-7');
                for (var j = 1; j <= 3; j++) {
                    var p2 = String(j).padStart(2, '0');
                    $('input[name="PLAN_' + p2 + '"]').val('');
                    $('input[name="FECHA_PLAN_' + p2 + '"]').val('');
                }
                console.log('✅ [PLANES] Formulario finalizado correctamente. Respuesta del servidor:', data);
                actualizarNotificacionPermanente(notif, 'Formulario completado exitosamente', 'success');
                setTimeout(function() {
                    sessionStorage.clear();
                    var href = window.location.href;
                    window.location.href = href + (href.indexOf('?') > -1 ? '&' : '?') + 'nocache=' + Date.now();
                }, 1500);
                resolve(true);
            } else {
                console.error('❌ [PLANES] Error al finalizar:', data.message);
                _onError(notif, data.message, resolve);
            }
        })
        .catch(function(e) { console.error('❌ [PLANES] Error de conexión:', e); _onConnectionError(notif, resolve); });
}

function _guardarSeccionGenerica(seccionId, resolve) {
    if (!formularioSessionId) formularioSessionId = sessionStorage.getItem('form_session_id');
    if (!formularioSessionId) {
        mostrarNotificacion('Tu sesión ha expirado. Por favor, recarga la página y comienza de nuevo.', 'error');
        resolve(false); return;
    }

    var nombreReal   = SECCIONES_MAP[seccionId] || seccionId;
    var $seccion     = $("#" + seccionId);

    // Validar imágenes obligatorias en Operaciones
    if (seccionId === 'seccion-2') {
        var faltantes = [];
        $seccion.find("input[type='file']").not("input[name='IMG_OBS_OPE']").each(function() {
            var fn = $(this).attr('name').replace(/\[\]$/, '');
            if (!imagenesSubidas[fn] || imagenesSubidas[fn].length === 0) faltantes.push(fn);
        });
        if (faltantes.length > 0) {
            mostrarNotificacion('Por favor, sube todas las imágenes requeridas para continuar.', 'warning');
            resolve(false); return;
        }
    }

    // Validar imágenes de Producto (solo si no es "No aplica")
    if (seccionId === 'seccion-4') {
        var faltantes4 = [];
        ['preg_04_07', 'preg_04_08'].forEach(function(fn) {
            var val = $('input[name="' + fn + '"]:checked').val();
            if (val && val !== 'NA' && val.trim() !== '') {
                var imgFn = fn.replace('preg_', 'IMG_');
                if (!imagenesSubidas[imgFn] || imagenesSubidas[imgFn].length === 0) faltantes4.push(imgFn);
            }
        });
        if (faltantes4.length > 0) {
            mostrarNotificacion('Por favor, sube todas las imágenes requeridas para continuar.', 'warning');
            resolve(false); return;
        }
    }

    // Mapeo observación → imagen
    var mapeoObsImg = {
        'obs_02_01': 'IMG_OBS_OPE',
        'obs_03_01': 'IMG_OBS_ADM',
        'obs_04_01': 'IMG_OBS_PRO',
        'obs_05_01': 'IMG_OBS_PER'
    };

    var preguntas = [];
    $seccion.find("input, select, textarea").not("input[type='file']").each(function() {
        var $el   = $(this);
        var rName = $el.attr("name");
        if (!rName || rName.startsWith('IMG_')) return;

        var valor = null;
        if ($el.is(":radio") && $el.is(":checked")) valor = $el.val();
        else if (!$el.is(":radio")) valor = $el.val();

        if (!valor || valor.trim() === "") return;

        var imgs = [];
        if (rName.startsWith('obs_')) {
            var imgFn = mapeoObsImg[rName] || null;
            if (imgFn) imgs = imagenesSubidas[imgFn] ? [].concat(imagenesSubidas[imgFn]) : [];
        } else if (rName.startsWith('preg_')) {
            var imgFnP = rName.replace('preg_', 'IMG_');
            imgs = imagenesSubidas[imgFnP] ? [].concat(imagenesSubidas[imgFnP]) : [];
        }

        preguntas.push({ codigo_pregunta: rName, respuesta: valor, imagenes: imgs });
    });

    console.log('📤 [' + nombreReal.toUpperCase() + '] Enviando ' + preguntas.length + ' preguntas:', preguntas);

    var notif = mostrarNotificacion('Guardando datos por favor espera...', 'loading', true);
    _fetchPost('/retail/save-seccion', { session_id: formularioSessionId, nombre_seccion: nombreReal, preguntas: preguntas })
        .then(function(data) {
            if (data.success) {
                seccionesGuardadas.add(seccionId);
                var stored = JSON.parse(sessionStorage.getItem('form_secciones') || '{}');
                stored[nombreReal] = preguntas;
                sessionStorage.setItem('form_secciones', JSON.stringify(stored));
                console.log('✅ [' + nombreReal.toUpperCase() + '] Sección guardada correctamente:', preguntas);
                _onSuccess(notif, nombreReal + ' guardada correctamente', resolve);
            } else {
                console.error('❌ [' + nombreReal.toUpperCase() + '] Error:', data.message);
                _onError(notif, data.message, resolve);
            }
        })
        .catch(function(e) { console.error('❌ [' + nombreReal.toUpperCase() + '] Error de conexión:', e); _onConnectionError(notif, resolve); });
}
