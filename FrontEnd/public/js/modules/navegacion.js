// =============================================================
// navegacion.js — Eventos, validaciones y control de flujo
// Depende de: config.js, ui.js, geo.js, imagenes.js, api.js
// =============================================================

function mostrarPantalla(id) {
    PANTALLAS_IDS.forEach(function(pid) {
        var el = document.getElementById(pid);
        if (el) el.style.display = 'none';
    });
    var target = document.getElementById(id);
    if (target) target.style.display = 'block';
}

function mostrarSeccion(indice) {
    SECCIONES.forEach(function(sec, i) {
        var $el = $("#" + sec);
        if (i === indice) $el.show();
        else $el.hide();
    });
}

function transformarValoresRadio() {
    var mapa = { "1": 0.2, "2": 0.4, "3": 0.6, "4": 0.8, "5": 1.0 };
    $("input[type='radio']:checked").each(function() {
        var v = mapa[$(this).val()] || $(this).val();
        $(this).attr("data-transformado", v);
    });
}

function correoActual() {
    var sel = $("#correo_tienda_select");
    if (sel.length && sel.val() === 'otro') return $("#correo_tienda_otro").val();
    if (sel.length) return sel.val();
    return $("#correo_tienda").val();
}

function checarHabilitarContinuar() {
    var sel = $("#correo_tienda_select");
    if (sel.length && sel.val() === 'otro') {
        return !!$("#correo_tienda_otro").val().match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/);
    }
    if (sel.length) return !!sel.val();
    return !!$("#correo_tienda").val().match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/);
}

// ---------------------------------------------------------------
// Inicialización — todo dentro del ready
// ---------------------------------------------------------------
$(document).ready(function() {

    // Inyectar estilo de modalidad activa
    $('<style>.modalidad-activa{background:#e6b200;color:#fff;box-shadow:0 2px 8px #e6b20080;}</style>').appendTo('head');

    // Inicializar fecha
    $("#fecha_inicio").val(new Date().toISOString());

    // Geolocalización inicial
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(p) { $("#ubicacion").val(p.coords.latitude + "," + p.coords.longitude); },
            function(e) { console.error("Error obteniendo la ubicación:", e); }
        );
    }

    // Modalidad visita
    $(document).on('click', '.modalidad-btn', function() {
        $('.modalidad-btn').removeClass('modalidad-activa');
        $(this).addClass('modalidad-activa');
        modalidadSeleccionada = $(this).data('modalidad');
        $('#modalidad_visita').val(modalidadSeleccionada);
        checarHabilitarContinuar();
    });

    $('#correo_tienda_select, #correo_tienda_otro').on('input change', checarHabilitarContinuar);

    // Mostrar/ocultar input correo "otro"
    var selectCorreo   = document.getElementById('correo_tienda_select');
    var inputCorreoOtro = document.getElementById('correo_tienda_otro');
    if (selectCorreo && inputCorreoOtro) {
        selectCorreo.addEventListener('change', function() {
            if (this.value === 'otro') {
                inputCorreoOtro.style.display = '';
                inputCorreoOtro.required      = true;
            } else {
                inputCorreoOtro.style.display = 'none';
                inputCorreoOtro.required      = false;
                inputCorreoOtro.value         = '';
            }
        });
        if (selectCorreo.value === 'otro') {
            inputCorreoOtro.style.display = '';
            inputCorreoOtro.required      = true;
        } else {
            inputCorreoOtro.style.display = 'none';
            inputCorreoOtro.required      = false;
        }
    }

    // Cargar países
    $.get("/retail/paises", function(data) {
        if (!Array.isArray(data)) { console.error("La respuesta no es un array:", data); return; }
        $("#pais").append('<option value="">Seleccione un país</option>');
        data.forEach(function(p) {
            $("#pais").append('<option value="' + p.value + '" data-nombre="' + p.label + '">' + p.label + '</option>');
        });
    });

    // Cargar zonas al cambiar país
    $("#pais").change(function() {
        var pais = $(this).val();
        $("#zona").empty().append('<option value="">Seleccione una zona</option>');
        $("#CRM_ID_TIENDA").empty().append('<option value="">Seleccione una tienda</option>');
        if (!pais) return;
        $.get('/retail/zonas/' + pais, function(data) {
            if (!Array.isArray(data)) { console.error("La respuesta no es un array:", data); return; }
            $("#zona").append(data.map(function(z) { return '<option value="' + z + '">' + z + '</option>'; }));
        });
    });

    // Cargar tiendas al cambiar zona
    $("#zona").change(function() {
        var pais = $("#pais").val();
        var zona = $(this).val();
        $("#CRM_ID_TIENDA").empty().append('<option value="">Seleccione una tienda</option>');
        if (!pais || !zona) return;
        $.get('/retail/tiendas/' + pais + '/' + zona, function(data) {
            if (!Array.isArray(data)) { console.error("La respuesta no es un array:", data); return; }
            data.forEach(function(t) {
                $("#CRM_ID_TIENDA").append(
                    '<option value="' + t.TIENDA + '" data-ubicacion="' + t.UBICACION + '" data-geo="' + (t.GEO || '') + '">' + t.TIENDA + '</option>'
                );
            });
            $('#CRM_ID_TIENDA').off('change.distancia').on('change.distancia', validarDistanciaTienda);
        });
    });

    // Logo → volver al inicio
    $("#logo-regreso").click(function() {
        indiceActual = 0;
        mostrarSeccion(indiceActual);
        location.href = "#intro";
    });

    // Botón Empezar (intro principal)
    $(".btnEmpezar1").click(function() {
        indiceActual = SECCIONES.indexOf("datos");
        mostrarSeccion(indiceActual);
    });

    // Botones Empezar por sección
    $(".btnEmpezar").click(function() {
        var seccion       = $(this).data("seccion");
        var introSeccion  = "intro-" + seccion;
        var pregSeccion   = "preguntas-" + seccion;
        if ($("#" + introSeccion).length && $("#" + pregSeccion).length) {
            $("#" + introSeccion).hide();
            $("#" + pregSeccion).show();
            indiceActual = SECCIONES.indexOf("seccion-" + seccion);
            mostrarSeccion(indiceActual);
        }
    });

    // Botón Siguiente — validación y avance
    $(".btnSiguiente").click(function(e) {
        e.preventDefault();

        var idActual = SECCIONES[indiceActual];

        // Validar modalidad en "datos"
        if (idActual === "datos" && !$('#modalidad_visita').val() && !modalidadSeleccionada) {
            return mostrarNotificacion("Seleccione la modalidad de la visita.", "warning");
        }

        // Validar correo en "datos"
        if (idActual === "datos") {
            var correo = correoActual();
            if (!correo || !correo.match(/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/)) {
                return mostrarNotificacion("Ingrese un correo válido.", 'warning');
            }
        }

        // Verificar que no haya subidas activas
        if (subidaEnProceso) {
            return mostrarNotificacion('Por favor espere a que termine la subida de la imagen', 'warning');
        }

        window.__modalidad_visita = modalidadSeleccionada;

        // Última sección: validar planes e imágenes, luego finalizar
        if (!dataSaved && indiceActual === SECCIONES.length - 1) {
            var planesValidos = 0;
            for (var pi = 1; pi <= 2; pi++) {
                var plan  = document.querySelector('input[name="PLAN_0' + pi + '"]');
                var fecha = document.querySelector('input[name="FECHA_PLAN_0' + pi + '"]');
                if (plan && fecha && plan.value.trim() !== '' && fecha.value.trim() !== '') planesValidos++;
            }
            if (planesValidos < 1) {
                return mostrarNotificacion("Debe completar al menos un Plan de Acción y su fecha.", "warning");
            }

            var imgsPendientes = [];
            ['IMG_OBS_OPE','IMG_OBS_ADM','IMG_OBS_PRO','IMG_OBS_PER'].forEach(function(fn) {
                var inp = document.querySelector("input[name='" + fn + "']");
                if (inp && inp.files.length > 0) {
                    var subidas = Object.keys(imagenesSubidas).filter(function(k) { return k.startsWith(fn); });
                    if (subidas.length === 0) imgsPendientes.push(fn);
                }
            });
            if (imgsPendientes.length > 0) {
                return mostrarNotificacion('Faltan por subir completamente: ' + imgsPendientes.join(', '), 'warning');
            }

            var totalImgs = Object.keys(imagenesSubidas).length;
            guardarSeccionActual().then(function(ok) {
                if (ok) dataSaved = true;
            });
            mostrarNotificacion('Fin del formulario! Se enviaron ' + totalImgs + ' imágenes. Espere a que terminen de enviarse los datos.', 'success');
            return;
        }

        // Validar campos visibles
        var seccionEl   = $("#" + SECCIONES[indiceActual]);
        var inputs      = seccionEl.find("input, select, textarea").filter(function() {
            return $(this).is(":visible") && !$(this).is(":disabled");
        }).toArray();
        var hayError    = inputs.some(function(inp) { return !inp.checkValidity(); });

        if (hayError) {
            mostrarNotificacion('Por favor, complete todos los campos requeridos antes de continuar.', 'warning');
            inputs.find(function(inp) { return !inp.checkValidity(); })[0].reportValidity();
            return;
        }

        // Validar variaciones KPI en seccion-6
        if (idActual === "seccion-6") {
            var variacionesOk = true;
            for (var ki = 1; ki <= 6; ki++) {
                var pre  = String(ki).padStart(2, '0');
                var $inp = $('input[name="var_06_' + pre + '"]');
                var val  = $inp.val();
                if (val === "" || isNaN(parseFloat(val))) {
                    $inp.addClass('input-error');
                    variacionesOk = false;
                } else {
                    $inp.removeClass('input-error');
                }
            }
            if (!variacionesOk) {
                return mostrarNotificacion('Por favor, ingrese valores numéricos válidos en todas las variaciones KPI.', 'warning');
            }
        }

        // Guardar según sección y avanzar
        if (idActual === "datos") {
            guardarDatos().then(function(ok) {
                if (!ok) return;
                var next = SECCIONES[indiceActual + 1];
                if (next) guardarProgreso(next);
                mostrarSeccion(++indiceActual);
            });
        } else if (idActual.startsWith("seccion-")) {
            guardarSeccionActual().then(function(ok) {
                if (!ok) return;
                var next = SECCIONES[indiceActual + 1];
                if (next) guardarProgreso(next);
                mostrarSeccion(++indiceActual);
            });
        } else {
            var next = SECCIONES[indiceActual + 1];
            if (next) guardarProgreso(next);
            mostrarSeccion(++indiceActual);
        }
    });

    // Keep-alive cada 3 minutos
    var intentosFallidosSesion = 0;
    setInterval(function() {
        fetch('/retail/keep-alive', { method: 'GET', credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) throw new Error('Código ' + r.status);
                intentosFallidosSesion = 0;
            })
            .catch(function(err) {
                intentosFallidosSesion++;
                console.warn('Intento fallido ' + intentosFallidosSesion + ':', err);
                if (intentosFallidosSesion >= 2) {
                    mostrarNotificacion('Tu sesión ha expirado o no se pudo renovar. Por favor recarga la página.', 'warning');
                }
            });
    }, 3 * 60 * 1000);

    // Inicializar subida incremental y restaurar progreso
    setupSubidaIncremental();
    restaurarProgreso().finally(function() {
        mostrarSeccion(indiceActual);
    });

});
