<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resultado extends Model
{
    // Especificar la conexión con BigQuery
    protected $connection = 'bigquery';

    // Nombre de la tabla en BigQuery
    protected $table = 'resultados';

    // No usar timestamps automáticos
    public $timestamps = false;

    // Especificar los campos que son asignables
    protected $fillable = [
        'id',
        'session_id',
        'FECHA_HORA_INICIO',
        'FECHA_HORA_FIN',
        'CORREO_REALIZO',
        'LIDER_ZONA',
        'PAIS',
        'ZONA',
        'TIENDA',
        'PREG_O1_01',
        'PREG_01_02',
        'PREG_01_03',
        'PREG_01_04',
        'PREG_01_05',
        'PREG_01_06',
        'PREG_01_07',
        'PREG_01_08',
        'PREG_01_09',
        'PREG_01_10',
        'PREG_01_11',
        'PREG_01_12',
        'PREG_01_13',
        'PREG_01_14',
        'OBS_01_01',
        'PREG_02_01',
        'PREG_02_02',
        'PREG_02_03',
        'PREG_02_04',
        'PREG_02_05',
        'PREG_02_06',
        'PREG_02_07',
        'PREG_02_08',
        'OBS_02_01',
        'PREG_03_01',
        'PREG_03_02',
        'PREG_03_03',
        'PREG_03_04',
        'PREG_03_05',
        'PREG_03_06',
        'PREG_03_07',
        'PREG_03_08',
        'OBS_03_01',
        'PREG_04_01',
        'PREG_04_02',
        'PREG_04_03',
        'PREG_04_04',
        'PREG_04_05',
        'PREG_04_06',
        'PREG_04_07',
        'PREG_04_08',
        'PREG_04_09',
        'PREG_04_10',
        'PREG_04_11',
        'PREG_04_12',
        'PREG_04_13',
        'PREG_04_14',
        'PREG_04_15',
        'OBS_04_01',
        'PREG_05_01',
        'PREG_05_02',
        'PREG_05_03',
        'PREG_05_04',
        'PREG_05_05',
        'PREG_05_06',
        'OBS_05_01',
        'PLAN_01',
        'FECHA_PLAN_01',
        'PLAN_02',
        'FECHA_PLAN_02',
        'PLAN_03',
        'FECHA_PLAN_03',
        'PLAN_04',
        'FECHA_PLAN_04',
        'PLAN_05',
        'FECHA_PLAN_05',
        'PLAN_ADIC',
        'FECHA_PLAN_ADIC',
        'IMG_OBS_OPR',
        'IMG_OBS_ADM',
        'IMG_OBS_PRO',
        'IMG_OBS_PER',
        'IMG_OBS_KPI'
    ];

    // Definir los tipos de datos para los campos si es necesario
    protected $casts = [
        'FECHA_HORA_INICIO' => 'datetime',
        'FECHA_HORA_FIN' => 'datetime',
        'FECHA_PLAN_01' => 'date',
        'FECHA_PLAN_02' => 'date',
        'FECHA_PLAN_03' => 'date',
        'FECHA_PLAN_04' => 'date',
        'FECHA_PLAN_05' => 'date',
        'FECHA_PLAN_ADIC' => 'date'
    ];
}
