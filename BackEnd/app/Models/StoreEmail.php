<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreEmail extends Model
{
    //
    use HasFactory;
    
    protected $table = 'crm_store_email';
    public $timestamps = false;

    protected $fillable = ['CORREO', 'FORMATO', 'PAIS', 'PAIS_TIENDA'];
}
