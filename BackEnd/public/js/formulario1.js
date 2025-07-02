$(document).ready(function () {

    let dataSaved = false;
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
        indiceActual = 0; // Reiniciar índice
        mostrarSeccion(indiceActual);
        location.href = "#intro";
    });

    // Cargar países
    $.get("/paises", function (data) {
        if (Array.isArray(data)) {
            $("#pais").append(data.map(p => `<option value="${p}">${p}</option>`));
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
            $.get(`/zonas/${pais}`, function (data) {
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
            $.get(`/tiendas/${pais}/${zona}`, function (data) {
                if (Array.isArray(data)) {
                    $("#CRM_ID_TIENDA").append(data.map(t =>
                        `<option value="${t.CRM_ID_TIENDA}" data-pais="${t.PAIS_TIENDA}" data-ubicacion="${t.UBICACION}">${t.PAIS_TIENDA} - ${t.UBICACION}</option>`));
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
                elemento.find(":input").attr("required", true); // Activar required en la sección visible
            } else {
                elemento.hide();
                elemento.find(":input").attr("required", false); // Desactivar required en las secciones ocultas
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

    // Recopilar respuestas antes de guardar
    function obtenerRespuestas() {
        transformarValoresRadio();
        let datos = {};

        let mapaCampos = {
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
            "obs_02_01": "OBS_01_01",
            "preg_03_01": "PREG_02_01",
            "preg_03_02": "PREG_02_02",
            "preg_03_03": "PREG_02_03",
            "preg_03_04": "PREG_02_04",
            "preg_03_05": "PREG_02_05",
            "preg_03_06": "PREG_02_06",
            "preg_03_07": "PREG_02_07",
            "preg_03_08": "PREG_02_08",
            "obs_03_01": "OBS_02_01",
            "preg_04_01": "PREG_03_01",
            "preg_04_02": "PREG_03_02",
            "preg_04_03": "PREG_03_03",
            "preg_04_04": "PREG_03_04",
            "preg_04_05": "PREG_03_05",
            "preg_04_06": "PREG_03_06",
            "preg_04_07": "PREG_03_07",
            "preg_04_08": "PREG_03_08",
            "obs_04_01": "OBS_03_01",
            "preg_05_01": "PREG_04_01",
            "preg_05_02": "PREG_04_02",
            "preg_05_03": "PREG_04_03",
            "preg_05_04": "PREG_04_04",
            "preg_05_05": "PREG_04_05",
            "preg_05_06": "PREG_04_06",
            "preg_05_07": "PREG_04_07",
            "preg_05_08": "PREG_04_08",
            "preg_05_09": "PREG_04_09",
            "preg_05_10": "PREG_04_10",
            "preg_05_11": "PREG_04_11",
            "preg_05_12": "PREG_04_12",
            "preg_05_13": "PREG_04_13",
            "preg_05_14": "PREG_04_14",
            "preg_05_15": "PREG_04_15",
            "obs_05_01": "OBS_04_01",
            "preg_06_01": "PREG_05_01",
            "preg_06_02": "PREG_05_02",
            "preg_06_03": "PREG_05_03",
            "preg_06_04": "PREG_05_04",
            "preg_06_05": "PREG_05_05",
            "preg_06_06": "PREG_05_06",
            "obs_06_01": "OBS_05_01"
        };

        // Collect standard fields first
        $("form").serializeArray().forEach(field => {
            let elemento = $(`[name='${field.name}']:checked`);
            let control_visitas = mapaCampos[field.name] || field.name;
            datos[control_visitas] = elemento.attr("data-transformado") || field.value;
        });

        // Specifically check for plan fields which might be missed
        $("input[name^='PLAN_'], input[name^='FECHA_PLAN_']").each(function () {
            let fieldName = $(this).attr("name");
            let fieldValue = $(this).val().trim();

            // Convertir fechas vacías en null (en lugar de "null" como texto)
            datos[fieldName] = fieldValue.length > 0 ? fieldValue : null;
        });

        // Add tienda info
        let tiendaSeleccionada = $("#CRM_ID_TIENDA option:selected");
        datos['tienda'] = tiendaSeleccionada.data('pais') + ' - ' + tiendaSeleccionada.data('ubicacion');

        // Add location data from hidden field
        datos['ubicacion'] = $("#ubicacion").val();

        // Standard form fields
        datos['correo_tienda'] = $("#correo_tienda").val();
        datos['jefe_zona'] = $("#jefe_zona").val();
        datos['pais'] = $("#pais").val();
        datos['zona'] = $("#zona").val();
        datos['CRM_ID_TIENDA'] = $("#CRM_ID_TIENDA").val();

        // Incluir archivos como objetos File reales
        ["IMG_OBS_OPE", "IMG_OBS_ADM", "IMG_OBS_PRO", "IMG_OBS_PER", "IMG_OBS_KPI"].forEach(fieldName => {
            const input = document.querySelector(`input[name='${fieldName}']`);
            if (input && input.files.length > 0) {
                datos[fieldName] = input.files[0]; // ✅ Aquí guardás el File real
            }
        });

        console.log("Datos a enviar:", datos);
        return datos;
    }


    function guardarSeccion(datos) {
        let formData = new FormData();
        ["IMG_OBS_OPE", "IMG_OBS_ADM", "IMG_OBS_PRO", "IMG_OBS_PER", "IMG_OBS_KPI"].forEach(nombreCampo => {
            let input = document.querySelector(`input[name='${nombreCampo}']`);
            if (input && input.files.length > 0) {
                formData.append(nombreCampo, input.files[0]); // Aquí va el archivo real
            }
        });

        for (let key in datos) {
            if (!["IMG_OBS_OPE", "IMG_OBS_ADM", "IMG_OBS_PRO", "IMG_OBS_PER", "IMG_OBS_KPI"].includes(key)) {
                formData.append(key, datos[key]);
            }
        }

        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('/guardar-seccion', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token
            },
            body: formData
        }).then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error("Error al guardar: " + response.status);
            }
        })
            .then(data => {
                console.log("Sección guardada exitosamente:", data);
            })
            .catch(error => {
                console.error("Error al enviar los datos:", error);
                alert("Error al guardar el formulario. Por favor intente nuevamente.");
            });
    }

    $(".btnSiguiente").click(function () {
        if (!$("#correo_tienda").val().includes('@') && indiceActual === secciones.indexOf("datos"))
            return alert("Ingrese un correo válido.");

        // Only save at the very last section
        if (!dataSaved && indiceActual === secciones.length - 1) {
            guardarSeccion(obtenerRespuestas());
            dataSaved = true;

            // Final submission redirect
            alert("¡Formulario completado! Gracias por tu tiempo.");
            window.location.replace("/formulario");
            return;
        }

        if (indiceActual < secciones.length - 1) {
            mostrarSeccion(++indiceActual);
        }
    });

    // Evento para manejar el botón "Empezar"
    $(".btnEmpezar1").click(function () {
        indiceActual = secciones.indexOf("datos");
        mostrarSeccion(indiceActual);
    });

    // Evento "Empezar" para avanzar desde introducción a preguntas
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

    // Mostrar la primera sección al cargar
    mostrarSeccion(indiceActual);
});
