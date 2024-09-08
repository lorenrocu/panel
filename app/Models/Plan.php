<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $table = 'planes';

    // Clave primaria personalizada
    protected $primaryKey = 'id_plan';

    // Campos que se pueden rellenar
    protected $fillable = [
        'nombre',
    ];

    // Relación: Un plan tiene muchos clientes
    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'id_plan', 'id_plan');
    }
}
