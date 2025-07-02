<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Operaciones;
use App\Models\Administracion;
use App\Models\Producto;
use App\Models\Personal;
use App\Models\Kpi;
use App\Models\PlanAccion;

class Formulario extends Model
{
    //
    use HasFactory;

    protected $fillable = ['pais', 'zona', 'CRM_ID_TIENDA'];
}
