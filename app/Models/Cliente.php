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
        'empresa_principal_id',
        'token',
        'id_account',
        'email',
    ];

    // Definir la relación con Plan
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan');
    }

    // Definir la relación con los atributos personalizados
    public function atributosPersonalizados()
    {
        return $this->hasMany(AtributoPersonalizado::class, 'id_cliente', 'id_cliente');
    }

    public function empresaPrincipal()
    {
        return $this->belongsTo(Cliente::class, 'empresa_principal_id');
    }

    public function clientesAsociados()
    {
        return $this->hasMany(Cliente::class, 'empresa_principal_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cliente_user', 'cliente_id', 'user_id');
    }

}
