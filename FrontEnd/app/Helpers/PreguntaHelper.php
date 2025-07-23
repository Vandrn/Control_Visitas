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
            'PREG_01_14' => 'Suficientes sillas o bancos en buen estado y limpios para clientes.',
            'PREG_01_15' => 'Cajas alzadoras de zapatos se usan en las exhibiciones.',
            'PREG_01_16' => 'No se usa cinta adhesiva (tape) en ningún lugar de la tienda.',
            'PREG_01_17' => 'No hay muebles dañados, rotos o quebrados en la tienda.',
            'PREG_01_18' => 'Área de caja ordenada y conforme a estándares autorizados.',
            'PREG_01_19' => 'Se ofrecen accesorios a los clientes en cada visita o compra.',
            'PREG_01_20' => 'Todas las luces de los muebles emiten luz amarilla intensa (3500-4000 lúmenes).',
            'PREG_01_21' => 'Pantallas de vitrina están posicionadas a 90 grados (vertical).',
            'PREG_01_22' => 'Azulejos, fórmica y piso no están dañados.',
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
            'PREG_03_02' => 'Artículos con etiqueta y precio correcto.',
            'PREG_03_03' => 'Cambios de precio realizados, firmados y archivados.',
            'PREG_03_04' => 'Promociones actualizadas y compartidas con el personal.',
            'PREG_03_05' => 'Implementación de planogramas.',
            'PREG_03_06' => 'Estilos disponibles representados por talla, sin zapato dañado o sucio.',
            'PREG_03_07' => 'Sandalias en exhibidores usan modeladores acrílicos.',
            'PREG_03_08' => 'Zapatos abiertos tienen acrílico.',
            'PREG_03_09' => 'Carteras con alzador en exhibiciones.',
            'OBS_03_01' => 'Observaciones del área de producto',

            // PERSONAL (PREG_04_XX)
            'PREG_04_02' => 'Personal con imagen presentable y uniforme según política.',
            'PREG_04_03' => 'Amabilidad en el recibimiento.',
            'PREG_04_05' => 'Disponibilidad para ayudar durante el recorrido.',
            'PREG_04_06' => 'ADOCKERS ofrecen ayuda a todos los clientes.',
            'PREG_04_07' => 'Ofrecen alternativas si no hay talla.',
            'PREG_04_08' => 'Ofrecen medir el pie de los niños.',
            'PREG_04_09' => 'Ofrecen diferentes zapatos para ajustar bien a los niños.',
            'PREG_04_10' => 'Elogian al cliente por su elección.',
            'PREG_04_11' => 'Clientes atendidos rápidamente en caja.',
            'PREG_04_12' => 'Cursos de Academia ADOC realizados.',
            'PREG_04_13' => 'Uso de la APP ADOCKY para atención e inventario.',
            'OBS_04_01' => 'Observaciones del área de personal',

            // KPIs
            'PREG_05_01' => 'VENTA - Cumplimiento de meta',
            'PREG_05_02' => 'CONVERSIÓN - Cumplimiento de meta',
            'PREG_05_03' => 'UPT - Cumplimiento de meta',
            'PREG_05_04' => 'DPT - Cumplimiento de meta',
            'PREG_05_05' => 'VENTA - Meta individual cumplida',
            'PREG_05_06' => 'CONVERSIÓN - Meta individual cumplida',
            'OBS_KPI'     => 'Observaciones KPIs',
        ];

        return $map[$codigo] ?? $codigo;
    }
}
