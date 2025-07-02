<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    //
    use HasFactory;
    
    protected $table = 'crm_stores'; // Nombre real de la tabla
    protected $primaryKey = 'CRM_ID_TIENDA';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['CRM_ID_TIENDA', 'FORMATO', 'PAIS', 'PAIS_TIENDA', 'UBICACION', 'ZONA'];
}
