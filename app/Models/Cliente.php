<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $primaryKey = 'id_cliente';  // Define la clave primaria
    protected $fillable = [
        'nombre_empresa',
        'id_plan',
        'token',
        'id_account',
        'email',
    ];

    // Definir la relación con Plan
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan');
    }
}
