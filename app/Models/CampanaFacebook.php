<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampanaFacebook extends Model
{
    use HasFactory;

    protected $table = 'campanas_facebook';

    // Asegúrate de que 'id_cliente' esté en los atributos rellenables
    protected $fillable = [
        'id_cliente',    // Agrega 'id_cliente' para poder insertar este valor
        'id_account',
        'id_campana',
        'utm_source',
        'utm_medium',
        'utm_term',
        'utm_content',
        'utm_campaign',
    ];

    // Relación con la tabla clientes a través de 'id_cliente'
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
