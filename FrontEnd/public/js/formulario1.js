// ======================================================
// Imports
// ======================================================
import { ocultarTodo, mostrarById, initNavegacion } from './formulario-navegacion.js';
import { guardarSeccion, obtenerEstructuraFinal } from './formulario-guardado.js';
import { setupSubidaIncremental, mostrarPreviewImagen, imagenesSubidas, subidaEnProceso } from './formulario-imagenes.js';
import { mostrarNotificacion } from './formulario-notificaciones.js';
import { debounce, bindSeleccionModalidad } from './formulario-utils.js';
import { obtenerUbicacionUsuario, calcularDistancia } from './formulario-ubicacion.js';

// ======================================================
// Estado global mínimo (evitar variables sueltas)
// ======================================================
let savedState = null;
let indiceActual = 0;
let modalidadSeleccionada = null;
let dataSaved = false;

// Se asume que estas existen en el DOM/layout
const secciones = [
  'intro',
  'datos',
  'preguntas-1', 'seccion-1',
  'preguntas-2', 'seccion-2',
  'preguntas-3', 'seccion-3',
  'preguntas-4', 'seccion-4',
  'preguntas-5', 'seccion-5',
  'preguntas-6', 'seccion-6'
];

// Helpers externos esperados
/* global formStorage, $ */

// ======================================================
// Inicio
// ======================================================
document.addEventListener('DOMContentLoaded', () => {
  initNavegacion();
  setupSubidaIncremental();
  inicializarCombosPaisZonaTienda();
  bindNavegacion();
  bindSeleccionModalidad();
  restaurarEstadoInicial();
  iniciarKeepAlive();

  setTimeout(() => {
    console.log("Reforzando visibilidad y orden inicial...");
    // Oculta todo de nuevo, por si algo quedó visible
    $('.formulario section, .formulario [id^="intro-"], .formulario [id^="seccion-"]').hide();

    // Carga el estado guardado o muestra la primera sección
    const state = formStorage.loadState?.();
    if (state?.indiceActual !== undefined) {
      mostrarSeccion(state.indiceActual);
    } else {
      mostrarSeccion(0);
    }
  }, 500);
});


// ======================================================
// Combo País → Zona → Tienda
// ======================================================
function inicializarCombosPaisZonaTienda() {
  // Restaurar país si existe
  try { 
    $("#pais").val(savedState?.general?.pais).trigger('change'); 
  } catch (e) { 
    console.warn(e); 
  }

  //Pais
    $.get('/retail/paises', (data) => {
    // ✅ Acepta tanto array plano como array de objetos
    if (!Array.isArray(data)) {
      console.warn('Respuesta inesperada de /retail/paises', data);
      return;
    }

    $("#pais").empty().append('<option value="">Seleccione un país</option>');

    data.forEach(p => {
      const value = typeof p === 'string' ? p : p.value;
      const label = typeof p === 'string' ? p : (p.label || p.value);
      $("#pais").append(`<option value="${value}">${label}</option>`);
    });

    // Restaurar país guardado si existe
    savedState ||= formStorage.loadState();
    const paisGuardado = savedState?.general?.pais;
    if (paisGuardado) $("#pais").val(paisGuardado).trigger('change');
  });

  // País → Zonas
  $(document).off('change.pais').on('change.pais', '#pais', function () {
    const pais = $(this).val();
    $("#zona").empty().append('<option value="">Seleccione una zona</option>');
    $("#CRM_ID_TIENDA").empty().append('<option value="">Seleccione una tienda</option>');
    if (!pais) return;

    $.get(`/retail/zonas/${pais}`, (data) => {
      if (!Array.isArray(data)) {
        console.warn('Respuesta inesperada de /retail/zonas', data);
        return;
      }

      data.forEach(z => {
        const val = typeof z === 'string' ? z : (z.ZONA || z.value || z.label);
        $("#zona").append(`<option value="${val}">${val}</option>`);
      });

      savedState ||= formStorage.loadState();
      const zonaGuardada = savedState?.general?.zona;
      if (zonaGuardada) $("#zona").val(zonaGuardada).trigger('change');
    });
  });

  // Zona → Tiendas
  $(document).off('change.zona').on('change.zona', '#zona', function () {
    const pais = $("#pais").val();
    const zona = $(this).val();
    $("#CRM_ID_TIENDA").empty().append('<option value="">Seleccione una tienda</option>');
    if (!pais || !zona) return;

    $.get(`/retail/tiendas/${pais}/${zona}`, (data) => {
      if (!Array.isArray(data)) {
        console.warn('Respuesta inesperada de /retail/tiendas', data);
        return;
      }

      data.forEach(t => {
        // ✅ Permite tanto objetos como strings
        const tienda = t.TIENDA || t.value || t.label || t;
        const ubicacion = t.UBICACION || '';
        const geo = t.GEO || '';
        $("#CRM_ID_TIENDA").append(
          `<option value="${tienda}" data-ubicacion="${ubicacion}" data-geo="${geo}">${tienda}</option>`
        );
      });

      savedState ||= formStorage.loadState();
      const tiendaGuardada = savedState?.general?.tienda;
      if (tiendaGuardada) $("#CRM_ID_TIENDA").val(tiendaGuardada).trigger('change');

      $('#CRM_ID_TIENDA').off('change.distancia').on('change.distancia', validarDistanciaTienda);
    });
  });
}

// ======================================================
// Navegación (mostrar secciones/intro/preguntas)
// ======================================================
function bindNavegacion() {
  // Empezar (ir a “datos”)
  $(document).off('click.empezar1').on('click.empezar1', '.btnEmpezar1', () => {
    const idx = secciones.indexOf('datos');
    indiceActual = idx >= 0 ? idx : 1;
    mostrarSeccion(indiceActual);
    debouncedSaveState();
  });

  // Empezar por sección concreta
  $(document).off('click.empezar').on('click.empezar', '.btnEmpezar', function () {
    const n = $(this).data('seccion');
    if (n == null) return;

    const wrapper = `preguntas-${n}`;
    const target = secciones.includes(wrapper) ? wrapper : `seccion-${n}`;
    const idx = secciones.indexOf(target);
    if (idx >= 0) {
      indiceActual = idx;
      mostrarSeccion(indiceActual);
    }
  });

  // Siguiente (validaciones + envío final)
  $(document).off('click.siguiente').on('click.siguiente', '.btnSiguiente', async function (e) {
    e.preventDefault();

    const seccionActualId = secciones[indiceActual];
    const $seccionActual = $(`#${seccionActualId}`);
    const inputsVisibles = $seccionActual.find('input, select, textarea')
      .filter(function () { return $(this).is(':visible') && !$(this).is(':disabled'); })
      .toArray();

    // Validar modalidad seleccionada
    if (!modalidadSeleccionada && !$('#modalidad_visita').val()) {
      mostrarNotificacion('Seleccione la modalidad de la visita.', 'warning');
      return;
    }
    modalidadSeleccionada = $('#modalidad_visita').val();
    window.__modalidad_visita = modalidadSeleccionada;

    // Validar correo (en “datos”)
    if (seccionActualId === 'datos') {
      const correo = (() => {
        const sel = $('#correo_tienda_select');
        if (sel.length && sel.val() === 'otro') return $('#correo_tienda_otro').val();
        if (sel.length) return sel.val();
        return $('#correo_tienda').val();
      })();
      if (!/^[a-zA-Z0-9._%+-]+@empresasadoc\.com$/.test(correo || '')) {
        mostrarNotificacion('Ingrese un correo válido.', 'warning');
        return;
      }
    }

    // Bloquear si hay subida en proceso
    if (subidaEnProceso) {
      mostrarNotificacion('⏳ Espere a que termine la subida de imágenes.', 'warning');
      return;
    }

    // Imágenes requeridas visibles en la sección
    const faltantes = imagenesRequeridasEn($seccionActual);
    if (faltantes.length) {
      const lista = faltantes.map(t => `⚠️ ${t}`).join('<br>');
      mostrarNotificacion(`Debe subir la(s) imagen(es) requerida(s):<br>${lista}`, 'warning');
      return;
    }

    // Validaciones por sección (KPIs en seccion-6)
    if (seccionActualId === 'seccion-6') {
      const ok = validarVariacionesKPI();
      if (!ok) {
        mostrarNotificacion('Ingrese valores numéricos válidos en todas las variaciones KPI.', 'warning');
        return;
      }
    }

    // Validación de required HTML5
    const hayError = inputsVisibles.some(i => !i.checkValidity());
    if (hayError) {
      mostrarNotificacion('Complete todos los campos requeridos antes de continuar.', 'warning');
      inputsVisibles.find(i => !i.checkValidity())?.reportValidity();
      return;
    }

    // Envío final al terminar
    if (!dataSaved && indiceActual === secciones.length - 1) {
      // Al menos 1 plan de acción
      let planesValidos = 0;
      for (let i = 1; i <= 2; i++) {
        const plan = document.querySelector(`input[name="PLAN_0${i}"]`);
        const fecha = document.querySelector(`input[name="FECHA_PLAN_0${i}"]`);
        if (plan?.value?.trim() && fecha?.value?.trim()) planesValidos++;
      }
      if (planesValidos < 1) {
        mostrarNotificacion('Debe completar al menos un Plan de Acción y su fecha.', 'warning');
        return;
      }

      // Confirmar que ninguna imagen requerida quedó sin subir
      const pendientes = ['IMG_OBS_OPE', 'IMG_OBS_ADM', 'IMG_OBS_PRO', 'IMG_OBS_PER'].filter(fn => {
        const input = document.querySelector(`input[name='${fn}']`);
        if (!input || input.files.length === 0) return false; // no seleccionada, no exigir
        // si se seleccionó, exigir que esté en el mapa subido
        return !Object.keys(imagenesSubidas).some(k => k.startsWith(fn));
      });
      if (pendientes.length) {
        mostrarNotificacion(`⚠️ Faltan por subir completamente: ${pendientes.join(', ')}`, 'warning');
        return;
      }

      if (subidaEnProceso) {
        mostrarNotificacion('⏳ Espere a que termine la subida de imágenes.', 'warning');
        return;
      }

      // Enviar
      const payload = obtenerEstructuraFinal(); // viene del módulo importado
      guardarSeccion(payload);                  // viene del módulo importado
      dataSaved = true;
      mostrarNotificacion('¡Formulario completado! Enviando…', 'success');
      return;
    }

    // Avanzar
    mostrarSeccion(++indiceActual);
    debouncedSaveState();
  });

  // Guardado local “debounced”
  var debouncedSaveState = debounce(() => {
    try {
      const state = formStorage.buildState?.() || {};
      state.indiceActual = indiceActual;
      formStorage.saveState?.(state);
    } catch (e) { console.warn('No se pudo guardar estado:', e); }
  }, 300);
}

// Mostrar una sección segura
function mostrarSeccion(indice) {
  ocultarTodo();
  const id = secciones[indice];
  if (!id) return;

  const $target = $('#' + id);
  if (!$target.length) {
    console.warn('mostrarSeccion: no se encontró', id);
    return;
  }

  // Mostrar el contenedor padre adecuado (por si estaba oculto)
  $target.parents('.contenedor-centrado, .solo-centro, .formulario').each(function () {
    $(this).css('display', 'block');
  });

  // Mostrar la sección actual
  $target.show().css('display', 'block');

  // Si la sección pertenece a preguntas-N, mostrar su seccion-N
  const wrapper = $target.closest('[id^="preguntas-"]');
  if (wrapper.length) wrapper.show();

  if (id.startsWith('preguntas-')) {
    const num = id.replace('preguntas-', '');
    $('#seccion-' + num).show();
  }

  // Asegurar visibilidad del contenedor principal
  $('.formulario').css('display', 'block');

  console.log('Mostrado:', id, 'visible:', $target.is(':visible'));
}


// ======================================================
// Restauración de estado
// ======================================================
function restaurarEstadoInicial() {
  // Mostrar inmediato si hay índice guardado
  savedState ||= formStorage.loadState?.();
  if (typeof savedState?.indiceActual === 'number') {
    indiceActual = savedState.indiceActual;
    mostrarSeccion(indiceActual);
  } else {
    mostrarSeccion(secciones.indexOf('intro') >= 0 ? secciones.indexOf('intro') : 0);
  }

  // Segundo pase (tras posibles renders async)
  setTimeout(() => {
    restoreStateAfterAjax();
    setTimeout(restoreStateAfterAjax, 300);
  }, 1500);
}

function restoreStateAfterAjax() {
  try {
    savedState ||= formStorage.loadState?.();
    if (!savedState) return;

    // Inputs
    formStorage.restoreInputsFromState?.(savedState.sections);

    // Modalidad
    if (savedState.modalidadSeleccionada) {
      modalidadSeleccionada = savedState.modalidadSeleccionada;
      $('#modalidad_visita').val(modalidadSeleccionada);
      $('.modalidad-btn').removeClass('modalidad-activa');
      $(`.modalidad-btn[data-modalidad='${modalidadSeleccionada}']`).addClass('modalidad-activa');
    }

    // Imágenes previas
    if (savedState.imagenesSubidas) {
      Object.assign(imagenesSubidas, savedState.imagenesSubidas);
      Object.entries(imagenesSubidas).forEach(([campo, urls]) => {
        const baseName = campo.replace(/(_\d{2})$/, '');
        let $input = $(`[name='${campo}']`);
        if (!$input.length) $input = $(`[name^='${baseName}']`).first();
        if (!$input.length) $input = $(`input[name^='${campo}']`).first();
        if (!$input.length) return;

        (Array.isArray(urls) ? urls : [urls]).forEach(url => {
          if (url) mostrarPreviewImagen($input, url, campo);
        });
      });
    }

    // Sección
    if (typeof savedState.indiceActual === 'number') {
      indiceActual = savedState.indiceActual;
      mostrarSeccion(indiceActual);
    }
  } catch (e) {
    console.warn('Error restaurando estado', e);
  }
}

// ======================================================
// Validaciones auxiliares
// ======================================================
function imagenesRequeridasEn($seccion) {
  const faltantes = [];
  $seccion.find("input[type='file'][required]").each(function () {
    const fieldName = this.name.replace(/\[\]$/, '');
    const tieneArchivo = this.files && this.files.length > 0;
    const fueSubida = Object.keys(imagenesSubidas).some(k => k.startsWith(fieldName));
    if (!tieneArchivo || !fueSubida) {
      let label = $(this).closest('.form-group, .mb-4, .mb-3').find('label').first().text().trim();
      if (!label) {
        label = this.placeholder || (fieldName.match(/(\d{2,})$/)?.[0] ? `Pregunta ${parseInt(RegExp.$1, 10)}` : fieldName);
      }
      faltantes.push(label);
    }
  });
  return faltantes;
}

function validarVariacionesKPI() {
  let ok = true;
  for (let i = 1; i <= 6; i++) {
    const $input = $(`input[name='var_06_0${i}']`);
    const val = $input.val();
    if (val === '' || isNaN(parseFloat(val))) {
      $input.addClass('input-error');
      ok = false;
    } else {
      $input.removeClass('input-error');
    }
  }
  return ok;
}

// ======================================================
// Distancia a tienda (solo cuando la visita NO es virtual)
// ======================================================
async function validarDistanciaTienda() {
  if (window.__modalidad_visita === 'virtual') {
    $('#mensaje-distancia').remove();
    return;
  }

  const select = document.getElementById('CRM_ID_TIENDA');
  const opt = select?.options[select.selectedIndex];
  $('#mensaje-distancia').remove();
  if (!opt?.value) return;

  const geo = opt.getAttribute('data-geo');
  if (!geo) {
    mostrarMensajeDistancia('⚠️ No se encontraron coordenadas para esta tienda', 'warning');
    return;
  }

  mostrarMensajeDistancia('📍 Verificando tu ubicación...', 'info');

  try {
    const pos = await obtenerUbicacionUsuario();
    const [latT, lngT] = geo.split(',').map(Number);
    const dist = calcularDistancia(pos.coords.latitude, pos.coords.longitude, latT, lngT);
    const m = Math.round(dist);

    if (m <= 150) {
      mostrarMensajeDistancia(`✅ Te encuentras a ${m} metros de la tienda`, 'success');
    } else {
      mostrarMensajeDistancia(`❌ Te encuentras a ${m} metros de la tienda (muy lejos)`, 'danger');
    }
  } catch (e) {
    console.error(e);
    mostrarMensajeDistancia('❌ No se pudo obtener tu ubicación', 'danger');
  }
}

function mostrarMensajeDistancia(mensaje, tipo) {
  $('#mensaje-distancia').remove();
  const estilos = {
    success: 'background:#10b981;color:#fff;',
    danger:  'background:#ef4444;color:#fff;',
    warning: 'background:#f59e0b;color:#fff;',
    info:    'background:#3b82f6;color:#fff;'
  }[tipo] || 'background:#6b7280;color:#fff;';

  $('#CRM_ID_TIENDA').after(`
    <div id="mensaje-distancia" style="
      margin-top:8px;padding:12px 16px;border-radius:8px;
      font-size:14px;font-weight:500;text-align:center;box-shadow:0 2px 4px rgba(0,0,0,.1);
      ${estilos}
    ">${mensaje}</div>
  `);
}

// ======================================================
// Keep-alive de sesión
// ======================================================
function iniciarKeepAlive() {
  let fallos = 0;
  const limite = 2;
  setInterval(() => {
    fetch('/retail/keep-alive', { method: 'GET', credentials: 'same-origin' })
      .then(r => { if (!r.ok) throw new Error(`Código ${r.status}`); fallos = 0; })
      .catch(err => {
        if (++fallos >= limite) mostrarNotificacion('⚠️ Tu sesión ha expirado o no se pudo renovar. Recarga la página.', 'warning');
        console.warn('Keep-alive fallo:', err);
      });
  }, 180000);
}
