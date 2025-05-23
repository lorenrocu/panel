<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder; // Agrega esta línea

class AtributoPersonalizado extends Model
{
    use HasFactory;

    protected $table = 'atributos_personalizados';

    protected $casts = [
        'opciones' => 'array', // Asegura que Laravel maneje este campo como un array
    ];

    protected $fillable = [
        'id_account',
        'nombre_atributo',
        'valor_atributo',
        'valor_por_defecto',
        'id_cliente',
        'attribute_key',
        'orden',
        'custom_attribute_id',
    ];

    protected static function booted()
    {
        static::addGlobalScope('orden', function (Builder $builder) {
            $builder->orderBy('orden');
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
