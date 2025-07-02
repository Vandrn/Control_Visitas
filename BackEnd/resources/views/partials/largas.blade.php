@php
$secciones = [
['seccion' => 2, 'titulo' => 'Operaciones', 'nombreCampo' => 'operaciones', 'preguntas' => [
"Pintura de tienda en buen estado. Interior/Exterior.",
"Vitrinas de tiendas limpias, con iluminación y acrílicos en buen estado.",
"Exhibición de producto en vitrina según estándares.",
"Sala de ventas limpia, ordenada y con iluminación en buen estado.",
"Aires acondicionados/ventiladores y escaleras en buen estado.",
"Repisas, mesas y muebles de exhibición limpios y en buen estado.",
"Mueble de caja limpio, ordenado y en buen estado",
"Equipo funcionando (radio, tel., cel., conteo de clientes, eq. de computo).",
"Utilización de la radio ADOC para ambientar la tienda.",
"Bodega limpia, con iluminación en buen estado y ordenada según manual.",
"Accesorios de limpieza ordenados y ubicados en el lugar adecuado.",
"Área de comida limpia y articulos personales ordenados en su área.",
"Baño limpio y ordenado",
"La tienda cuenta con suficientes sillas o bancos en buen estado (limpios y lavados) para que los clientes se prueben los zapatos (según planograma o layout). NOTA: Si los sillones están sucios deben mandarse a lavar.",
"Observaciones del área de operaciones"
]],
['seccion' => 3, 'titulo' => 'Administración', 'nombreCampo' => 'administracion', 'preguntas' => [
"Cuenta de orden al día.",
"Documentos de transferencias y envíos ingresados al sistema al día",
"Remesas de efectivo al día e ingresados al sistema",
"Libro de cuadre de efectivo y caja chica al día",
"Libro de horarios al día y firmados por los empleados",
"Conteo efectuados según lineamientos establecidos.",
"Pizarras y folders Friedman actualizados.",
"Files actualizados.",
"Observaciones del área de administración."
]],
['seccion' => 4, 'titulo' => 'Producto', 'nombreCampo' => 'producto', 'preguntas' => [
"Nuevos estilos exhibidos en sala de venta.",
"Artículos exhibidos con su etiqueta y precio correcto. Nota: Si un zapato llega dañado de fábrica reportarlo de inmediato y retírelo del piso de venta.",
"Cambios de precio realizado, firmado y archivado. Nota: Es prohibido colocar otro precio que no sea el oficial.",
"Promociones actualizadas y compartidas con todo el personal.",
"Reporte 80/20 revisado semanalmente.",
"Implementación de planogramas(Producto, POP, Manuales).",
"En las exhibiciones están todos los estilos disponibles en la tienda representados por talla (sin ningún zapato dañado o sucio).",
"Todas las sandalias en exhibidores y/o mesas usan modeladores acrílicos.",
"Observaciones del área Producto."
]],
['seccion' => 5, 'titulo' => 'Personal', 'nombreCampo' => 'personal', 'preguntas' => [
"Cumplimiento de las marcaciones (4 por día).",
"Personal con imagen presentable, con su respectivo uniforme según política.",
"Personal cumple los 5 estándares NO negociables.",
"Amabilidad en el recibimiento de los clientes.",
"Cumplimiento de protocolos de bioseguridad.",
"Disponibilidad del personal para ayudar durante el recorrido, selección y prueba de calzado.",
"Nuestros ADOCKERS ofrecen ayuda a todos los clientes.",
"Nuestros ADOCKERS ofrecen encontrar la talla que el cliente pide y si no hay talla, ofrecen alternativas.",
"Nuestros ADOCKERS ofrecen medir el pie de los niños.",
"Se ofrecen diferentes zapatos para que ajuste el pie correctamente cuando hay niños.",
"Nuestros ADOCKERS elogian a los clientes por su elección de producto.",
"Nuestros clientes son atendidos rápidamente en caja.",
"¿Han realizado los cursos de Academia ADOC?",
"¿Adockers hacen uso de la APP ADOCKY cuando atienden a los clientes en el piso de venta?",
"Adockers hacen uso de la APP ADOCKY para realizar la representación de inventario.",
"Observaciones del área Personal."
]]
];
@endphp

@foreach ($secciones as $seccion)
<!-- Introducción de la Sección -->
<div id="intro-{{ $seccion['seccion'] }}" class="introduccion" style="display: none;">
    <h2 class="titulo-intro">Elementos a evaluar del área de {{ $seccion['titulo'] }}</h2>
    <p>Evalúe los siguientes elementos en la tienda del 1 al 5, siendo 1 el peor y 5 el mejor.</p><br><br><br><br><br><br>
    <button type="button" class="boton btnEmpezar" data-seccion="{{ $seccion['seccion'] }}">Empezar</button>
</div>

<!-- Preguntas de la Sección -->
<div id="preguntas-{{ $seccion['seccion'] }}" style="display: none;">
    @include('partials.preguntas', $seccion)
</div>
@endforeach