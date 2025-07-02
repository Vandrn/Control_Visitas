<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultadoTemporal extends Model
{
    use HasFactory;

    protected $table = 'resultados_temporales'; // Nombre de la tabla en la BD

    protected $fillable = [
        'session_id',
        'fecha',
        'pais',
        'zona',
        'tienda',
        'correo_tienda',
        'jefe_zona',
        'pintura_tienda_buen_estado',
        'vitrinas_limpias_iluminacion_buen_estado',
        'exhibicion_producto_vitrina_estandares',
        'sala_ventas_limpia_ordenada_iluminacion',
        'aires_ventiladores_escaleras_buen_estado',
        'repisas_mesas_muebles_limpios_buen_estado',
        'mueble_caja_limpio_ordenado_buen_estado',
        'equipo_funcionando_radio_tel_cel_computo',
        'utilizacion_radio_adoc_ambientar_tienda',
        'bodega_limpia_iluminacion_ordenada_manual',
        'accesorios_limpieza_ordenados_lugar_adecuado',
        'area_comida_limpia_articulos_ordenados',
        'bano_limpio_ordenado',
        'sillas_buen_estado_limpias_lavadas',
        'cuenta_orden_dia',
        'documentos_transferencias_envios_sistema_dia',
        'remesas_efectivo_dia_ingresadas_sistema',
        'libro_cuadre_efectivo_caja_chica_dia',
        'libro_horarios_dia_firmado_empleados',
        'conteo_efectuados_lineamientos_establecidos',
        'pizarras_folders_friedman_actualizados',
        'files_actualizados',
        'nuevos_estilos_exhibidos_sala_venta',
        'articulos_exhibidos_etiqueta_precio_correcto',
        'cambios_precio_realizado_firmado_archivado',
        'promociones_actualizadas_compartidas_personal',
        'reporte_80_20_revisado_semanalmente',
        'implementacion_planogramas_producto_pop_manuales',
        'exhibiciones_estilos_disponibles_representados_talla',
        'sandalias_exhibidores_mesas_modeladores_acrilicos',
        'cumplimiento_marcaciones_4_por_dia',
        'personal_imagen_presentable_uniforme_politica',
        'personal_cumple_5_estandares_no_negociables',
        'amabilidad_recibimiento_clientes',
        'cumplimiento_protocolos_bioseguridad',
        'disponibilidad_personal_ayuda_seleccion_calzado',
        'adockers_ayuda_todos_clientes',
        'adockers_ofrecen_talla_o_alternativas',
        'adockers_medir_pie_ninos',
        'zapatos_ajustan_pie_correctamente_ninos',
        'adockers_elogian_clientes_eleccion_producto',
        'clientes_atendidos_rapidamente_caja',
        'realizaron_cursos_academia_adoc',
        'adockers_usando_app_adocky_piso_venta',
        'adockers_app_adocky_representacion_inventario',
        'observaciones_operaciones',
        'observaciones_administracion',
        'observaciones_producto',
        'observaciones_personal',
        'observaciones_kpis',
        'punto_adicional',
        'fecha_punto_adicional',
        // Añadir campos de imágenes de observaciones
        'imagen_observaciones_kpi',
        'imagen_observaciones_producto',
        'imagen_observaciones_personal',
        'imagen_observaciones_operaciones',
        'imagen_observaciones_administracion',
    ];

    // Planes de acción dinámicos
    public function getFillable()
    {
        $planes = [];
        for ($i = 1; $i <= 5; $i++) {
            $planes[] = "plan_accion_$i";
            $planes[] = "fecha_plan_accion_$i";
        }
        return array_merge($this->fillable, $planes);
    }
}
