<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtributoPersonalizado extends Model
{
    use HasFactory;

    protected $table = 'atributos_personalizados';

    protected $fillable = [
        'id_account',
        'nombre_atributo',
        'valor_atributo',
        'valor_por_defecto',  // Asegúrate de que esto esté aquí
        'id_cliente',
        'attribute_key'
    ];
}
