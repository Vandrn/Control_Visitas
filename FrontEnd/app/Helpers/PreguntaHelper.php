<?php

namespace App\Helpers;

class PreguntaHelper
{
    public static function nombreBonito($codigo)
    {
        $map = [
            // OPERACIONES (PREG_01_XX)
            'PREG_01_01' => 'Pintura de tienda en buen estado. Interior/Exterior.',
            'PREG_01_02' => 'Vitrinas de tiendas limpias, con iluminación y acrílicos en buen estado.',
            'PREG_01_03' => 'Exhibición de producto en vitrina según estándares.',
            'PREG_01_04' => 'Sala de ventas limpia, ordenada y con iluminación en buen estado.',
            'PREG_01_05' => 'Aires acondicionados/ventiladores y escaleras en buen estado.',
            'PREG_01_06' => 'Repisas, mesas y muebles de exhibición limpios y en buen estado.',
            'PREG_01_07' => 'Mueble de caja limpio, ordenado y en buen estado.',
            'PREG_01_08' => 'Equipo funcionando (radio, tel., cel., conteo de clientes, eq. de computo).',
            'PREG_01_09' => 'Utilización de la radio ADOC para ambientar la tienda.',
            'PREG_01_10' => 'Bodega limpia, con iluminación en buen estado y ordenada según manual.',
            'PREG_01_11' => 'Accesorios de limpieza ordenados y ubicados en el lugar adecuado.',
            'PREG_01_12' => 'Área de comida limpia y artículos personales ordenados en su área.',
            'PREG_01_13' => 'Baño limpio y ordenado.',
            'PREG_01_14' => 'La tienda cuenta con suficientes sillas o bancos en buen estado (limpios y lavados) para que los clientes se prueben los zapatos (según planograma o layout). NOTA: Si los sillones están sucios deben mandarse a lavar.',
            'PREG_01_15' => 'Las cajas alzadoras de zapatos se usan en las exhibiciones.',
            'PREG_01_16' => 'No se usa cinta adhesiva (tape) en ningún lugar de la tienda.',
            'PREG_01_17' => 'No hay muebles dañados, rotos o quebrados en la tienda.',
            'PREG_01_18' => 'El área de caja está ordenada y conforme a los estándares autorizados y en servicio.',
            'PREG_01_19' => 'Se ofrecen accesorios a los clientes en cada visita o compra.',
            'PREG_01_20' => 'Todas las luces de los muebles emiten luz amarilla intensa (3500-4000 lúmenes).',
            'PREG_01_21' => 'Pantallas de vitrina están posicionadas a 90 grados (vertical).',
            'PREG_01_22' => 'Los azulejos, la fórmica y el piso no están dañados en ningún lugar de la tienda.',
            'OBS_01_01' => 'Observaciones del área de operaciones',

            // ADMINISTRACIÓN (PREG_02_XX)
            'PREG_02_01' => 'Cuenta de orden al día.',
            'PREG_02_02' => 'Documentos de transferencias y envíos ingresados al sistema al día.',
            'PREG_02_03' => 'Remesas de efectivo al día e ingresados al sistema.',
            'PREG_02_04' => 'Libro de cuadre de efectivo y caja chica al día.',
            'PREG_02_05' => 'Libro de horarios al día y firmados por los empleados.',
            'PREG_02_06' => 'Conteo efectuado según lineamientos.',
            'PREG_02_08' => 'Files actualizados.',
            'OBS_02_01' => 'Observaciones del área de administración',

            // PRODUCTO (PREG_03_XX)
            'PREG_03_01' => 'Nuevos estilos exhibidos en sala de venta.',
            'PREG_03_02' => 'Artículos exhibidos con su etiqueta y precio correcto. Nota: Si un zapato llega dañado de fábrica reportarlo de inmediato y retírelo del piso de venta.',
            'PREG_03_03' => 'Cambios de precio realizado, firmado y archivado. Nota: Es prohibido colocar otro precio que no sea el oficial.',
            'PREG_03_04' => 'Promociones actualizadas y compartidas con el personal.',
            'PREG_03_05' => 'Implementación de planogramas(Producto, POP, Manuales).',
            'PREG_03_06' => 'En las exhibiciones están todos los estilos disponibles en la tienda representados por talla (sin ningún zapato dañado o sucio).',
            'PREG_03_07' => 'Todas las sandalias en exhibidores y/o mesas usan modeladores acrílicos.',
            'PREG_03_08' => 'Todas las sandalias y zapatos abiertos tienen un acrílico.',
            'PREG_03_09' => 'Todas las carteras tienen un alzador en las exhibiciones.',
            'OBS_03_01' => 'Observaciones del área de producto',

            // PERSONAL (PREG_04_XX)
            'PREG_04_02' => 'Personal con imagen presentable, con su respectivo uniforme según política.',
            'PREG_04_03' => 'Amabilidad en el recibimiento de los clientes.',
            'PREG_04_05' => 'Disponibilidad del personal para ayudar durante el recorrido, selección y prueba de calzado.',
            'PREG_04_06' => 'Nuestros ADOCKERS ofrecen ayuda a todos los clientes.',
            'PREG_04_07' => 'Nuestros ADOCKERS ofrecen encontrar la talla que el cliente pide y si no hay talla, ofrecen alternativas.',
            'PREG_04_08' => 'Nuestros ADOCKERS ofrecen medir el pie de los niños.',
            'PREG_04_09' => 'Se ofrecen diferentes zapatos para que ajuste el pie correctamente cuando hay niños.',
            'PREG_04_10' => 'Nuestros ADOCKERS elogian a los clientes por su elección de producto.',
            'PREG_04_11' => 'Nuestros clientes son atendidos rápidamente en caja.',
            'PREG_04_12' => '¿Han realizado los cursos de Academia ADOC?',
            'PREG_04_13' => '¿Adockers hacen uso de la APP ADOCKY cuando atienden a los clientes en el piso de venta?',
            'PREG_04_14' => 'Adockers hacen uso de la APP ADOCKY para realizar la representación de inventario.',
            'OBS_04_01' => 'Observaciones del área de personal',

            // KPIs
            'PREG_05_01' => 'VENTA - Cumplimiento de meta',
            'PREG_05_02' => 'MARGEN - Cumplimiento de meta',
            'PREG_05_03' => 'CONVERSION - Cumplimiento de meta',
            'PREG_05_04' => 'UPT - Cumplimiento de meta',
            'PREG_05_05' => 'DPT - Cumplimiento de meta',
            'PREG_05_06' => 'NPS - Cumplimiento de meta',
            'OBS_KPI'     => 'Observaciones KPIs',
        ];

        return $map[$codigo] ?? $codigo;
    }
}
