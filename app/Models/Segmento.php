<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Segmento extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'tipo_de_segmento', 'cliente_id'];

    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    protected static function boot()
    {
        parent::boot();

        // Evento antes de crear un segmento
        static::creating(function ($segmento) {
            // Obtener el usuario logueado
            $userId = Auth::id();

            // Buscar el cliente asociado al usuario logueado en la tabla cliente_user
            $clienteUser = \App\Models\ClienteUser::where('user_id', $userId)->first();

            if ($clienteUser) {
                $segmento->cliente_id = $clienteUser->cliente_id;
            } else {
                throw new \Exception('No se pudo encontrar un cliente asociado al usuario logueado.');
            }
        });
    }

    public function contactos()
    {
        return $this->belongsToMany(Contacto::class, 'contacto_segmento', 'segmento_id', 'contacto_id');
    }
}
