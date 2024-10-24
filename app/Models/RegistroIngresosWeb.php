<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroIngresosWeb extends Model
{
    use HasFactory;

    protected $table = 'registro_ingresos_web';

    protected $fillable = [
        'id_account',
        'registro_id',
        'utms',
        'hora',
        'fecha',
    ];
}

