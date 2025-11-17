// ======================================================
// Navegación del formulario
// ======================================================
let order = [];

// ⬇️ Exportadas
export function initNavegacion() {
  // Buscar TODAS las secciones dentro del contenedor principal
  const selectorAll =
    '.formulario #intro, .formulario #datos, .formulario [id^="intro-"], .formulario [id^="preguntas-"], .formulario [id^="seccion-"]';

  const nodes = Array.from(document.querySelectorAll(selectorAll));

  order = nodes
    .map((n) => n.id)
    .filter(Boolean)
    .filter((v, i, a) => a.indexOf(v) === i);

  console.log('Orden detectado (DOM order):', order);

  // Inicializar eventos
  initHandlers();

  // Ocultar todo al inicio
  ocultarTodo();

  // Mostrar solo la intro principal (#intro)
  if ($('#intro').length) {
    $('#intro').show();
    $('[id^="intro-"]').hide(); // Oculta intro-2, intro-3, etc.
  } else {
    // Fallback: si no hay #intro, mostrar el primero intro-x
    const primerIntro = order.find((id) => id.startsWith('intro-'));
    if (primerIntro) mostrarById(primerIntro);
  }

  console.log('Navegación activa. Orden:', order);
}

// ⬇️ Exportadas
export function ocultarTodo() {
  // Ocultar todas las secciones principales
  $('#intro, #datos, [id^="intro-"], [id^="preguntas-"], [id^="seccion-"]').hide();

  // Mantener visibles los contenedores base
  $('.formulario, .contenedor-centrado').css('display', 'block');
}

// ⬇️ Exportadas
export function mostrarById(id) {
  $('#intro, #datos, [id^="intro-"], [id^="preguntas-"], [id^="seccion-"]').hide();

  const $el = $('#' + id);
  if (!$el.length) {
    console.warn('mostrarById: no existe', id);
    return false;
  }

  // Mostrar contenedores padres necesarios
  $el.parents('.contenedor-centrado, .solo-centro, .formulario').each(function () {
    $(this).css('display', 'block');
  });

  // Mostrar el elemento actual
  $el.show().css('display', 'block');

  $el.addClass('visible-forzada');

  // Si hay wrapper de preguntas
  const wrapper = $el.closest('[id^="preguntas-"]');
  if (wrapper.length) wrapper.show();

  if (id.startsWith('preguntas-')) {
    const num = id.replace('preguntas-', '');
    $('#seccion-' + num).show();
  }

  $('.formulario').css('display', 'block');

  console.log('Mostrado:', id, 'visible:', $el.is(':visible'));
  return true;
}


// ======================================================
// Inicializar eventos
// ======================================================
function initHandlers() {
  // Botón de intro principal → va a "datos"
  $('.btnEmpezar1')
    .off('click.nav')
    .on('click.nav', function () {
      console.log('Click en btnEmpezar1');
      mostrarById('datos');
    });

    // Botón "Continuar" dentro de la pantalla #datos → va a seccion-1
  $('#btnContinuarDatos')
    .off('click.nav')
    .on('click.nav', function () {
      console.log('Click en Continuar (datos)');
      mostrarById('seccion-1');
    });

  // Botones de intros de cada área (intro-2, intro-3, etc.)
  $('.btnEmpezar')
    .off('click.nav')
    .on('click.nav', function () {
      const n = $(this).data('seccion');
      console.log('Click en btnEmpezar, seccion:', n);
      if (n === undefined) return;

      const wrapper = 'preguntas-' + n;
      if (order.indexOf(wrapper) !== -1) {
        mostrarById(wrapper);
      }
    });

  // Botón de siguiente
  $('.btnSiguiente')
    .off('click.nav')
    .on('click.nav', function (e) {
      e.preventDefault();
      let visible = null;

      for (let i = 0; i < order.length; i++) {
        if ($('#' + order[i]).is(':visible')) {
          visible = order[i];
          break;
        }
      }

      const idx = order.indexOf(visible);
      console.log('Siguiente desde:', visible, 'idx:', idx);

      if (idx >= 0 && idx < order.length - 1) {
        mostrarById(order[idx + 1]);
      }
    });

    // Botones "Continuar" genéricos (con data-next)
  $('[data-next]')
    .off('click.nav')
    .on('click.nav', function () {
      const nextId = $(this).data('next');
      if (nextId) {
        console.log('➡️ Ir a:', nextId);
        mostrarById(nextId);
      }
    });

}
