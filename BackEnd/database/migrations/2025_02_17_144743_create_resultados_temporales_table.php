<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resultados_temporales', function (Blueprint $table) {
            $table->id();
            $table->string('session_id'); // Identificador único para la sesión del usuario
            $table->date('fecha')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('pais');
            $table->string('zona');
            $table->string('tienda');
            $table->string('correo_tienda');
            $table->string('jefe_zona');

            // Preguntas de Operaciones
            $table->string('pintura_tienda_buen_estado');
            $table->string('vitrinas_limpias_iluminacion_buen_estado');
            $table->string('exhibicion_producto_vitrina_estandares');
            $table->string('sala_ventas_limpia_ordenada_iluminacion');
            $table->string('aires_ventiladores_escaleras_buen_estado');
            $table->string('repisas_mesas_muebles_limpios_buen_estado');
            $table->string('mueble_caja_limpio_ordenado_buen_estado');
            $table->string('equipo_funcionando_radio_tel_cel_computo');
            $table->string('utilizacion_radio_adoc_ambientar_tienda');
            $table->string('bodega_limpia_iluminacion_ordenada_manual');
            $table->string('accesorios_limpieza_ordenados_lugar_adecuado');
            $table->string('area_comida_limpia_articulos_ordenados');
            $table->string('bano_limpio_ordenado');
            $table->string('sillas_buen_estado_limpias_lavadas');

            // Preguntas de Administración
            $table->string('cuenta_orden_dia');
            $table->string('documentos_transferencias_envios_sistema_dia');
            $table->string('remesas_efectivo_dia_ingresadas_sistema');
            $table->string('libro_cuadre_efectivo_caja_chica_dia');
            $table->string('libro_horarios_dia_firmado_empleados');
            $table->string('conteo_efectuados_lineamientos_establecidos');
            $table->string('pizarras_folders_friedman_actualizados');
            $table->string('files_actualizados');

            // Preguntas de Producto
            $table->string('nuevos_estilos_exhibidos_sala_venta');
            $table->string('articulos_exhibidos_etiqueta_precio_correcto');
            $table->string('cambios_precio_realizado_firmado_archivado');
            $table->string('promociones_actualizadas_compartidas_personal');
            $table->string('reporte_80_20_revisado_semanalmente');
            $table->string('implementacion_planogramas_producto_pop_manuales');
            $table->string('exhibiciones_estilos_disponibles_representados_talla');
            $table->string('sandalias_exhibidores_mesas_modeladores_acrilicos');

            // Preguntas de Personal
            $table->string('cumplimiento_marcaciones_4_por_dia');
            $table->string('personal_imagen_presentable_uniforme_politica');
            $table->string('personal_cumple_5_estandares_no_negociables');
            $table->string('amabilidad_recibimiento_clientes');
            $table->string('cumplimiento_protocolos_bioseguridad');
            $table->string('disponibilidad_personal_ayuda_seleccion_calzado');
            $table->string('adockers_ayuda_todos_clientes');
            $table->string('adockers_ofrecen_talla_o_alternativas');
            $table->string('adockers_medir_pie_ninos');
            $table->string('zapatos_ajustan_pie_correctamente_ninos');
            $table->string('adockers_elogian_clientes_eleccion_producto');
            $table->string('clientes_atendidos_rapidamente_caja');
            $table->string('realizaron_cursos_academia_adoc');
            $table->string('adockers_usando_app_adocky_piso_venta');
            $table->string('adockers_app_adocky_representacion_inventario');

            //kpis
            $table->string('kpi_venta');
            $table->string('kpi_margen');
            $table->string('kpi_conversion');
            $table->string('kpi_upt');
            $table->string('kpi_dpt');
            $table->string('kpi_nps');

            // Añadir campos de imágenes de observaciones
            $table->string('imagen_observaciones_kpi')->nullable();
            $table->string('imagen_observaciones_producto')->nullable();
            $table->string('imagen_observaciones_personal')->nullable();
            $table->string('imagen_observaciones_operaciones')->nullable();
            $table->string('imagen_observaciones_administracion')->nullable();

            // Observaciones por área
            $table->text('observaciones_operaciones')->nullable();
            $table->text('observaciones_administracion')->nullable();
            $table->text('observaciones_producto')->nullable();
            $table->text('observaciones_personal')->nullable();
            $table->text('observaciones_kpi')->nullable();

            // Planes de acción
            for ($i = 1; $i <= 5; $i++) {
                $table->text("plan_$i")->nullable();
                $table->date("fecha_$i")->nullable();
            }

            $table->text('plan_opt')->nullable();
            $table->date('fecha_opcional')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_temporales');
    }
};
