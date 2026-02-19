// =============================================================
// config.js — Constantes, estado global y helpers de utilidad
// =============================================================

// --- Constantes de secciones ---
var SECCIONES = [
    "intro", "datos", "seccion-1",
    "intro-2", "seccion-2",
    "intro-3", "seccion-3",
    "intro-4", "seccion-4",
    "intro-5", "seccion-5",
    "seccion-6", "seccion-7"
];

var PANTALLAS_IDS = [
    'intro','datos','seccion-1',
    'intro-2','preguntas-2','seccion-2',
    'intro-3','preguntas-3','seccion-3',
    'intro-4','preguntas-4','seccion-4',
    'intro-5','preguntas-5','seccion-5',
    'seccion-6','seccion-7'
];

var SECCIONES_MAP = {
    'seccion-2': 'Operaciones',
    'seccion-3': 'Administración',
    'seccion-4': 'Producto',
    'seccion-5': 'Personal',
    'seccion-6': 'KPIs',
    'seccion-7': 'Final'
};

var SECCIONES_SIN_IMAGENES = ['seccion-3', 'seccion-6'];
var SECCIONES_CON_NO_APLICA = ['seccion-4', 'seccion-5'];

// --- Estado global del formulario ---
var formularioSessionId = null;
var modalidadSeleccionada  = '';
var dataSaved              = false;
var imagenesSubidas        = {};
var subidaEnProceso        = false;
var indiceActual           = 0;
var datosSeccion1          = {};
var seccionesGuardadas     = new Set();

// --- Helpers de error ---
function esErrorTecnico(mensaje) {
    if (!mensaje) return false;
    var tecnicas = [
        'STRUCT','JSON','type','Type','undefined','Value of type',
        'exception','INVALID_ARGUMENT','SYNTAX_ERROR',
        'PARSE','parsing','unexpected','Unexpected','Cannot',
        'cannot','must','required','constraint','foreign key',
        'database','BigQuery','SQL','query','parameter','token',
        'MERGE','INSERT','UPDATE','DELETE','invalidQuery','FAILED'
    ];
    return tecnicas.some(function(p) { return mensaje.includes(p); });
}

function obtenerMensajeUsuario(errorMessage, tipo) {
    tipo = tipo || 'error';
    console.error('Error capturado:', errorMessage);
    if (tipo === 'error' && esErrorTecnico(errorMessage)) {
        console.warn('Es error técnico - mostrando mensaje amigable');
        return 'Hubo un problema técnico. Por favor, contacta al administrador.';
    }
    return errorMessage || 'Ocurrió un error. Por favor, intenta de nuevo.';
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function getSessionId() {
    return formularioSessionId
        || sessionStorage.getItem('form_session_id')
        || localStorage.getItem('form_session_id');
}
