<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ClienteUser;

class Plantilla extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'mensaje', 'imagen', 'id_cliente'];

    // Añadir un "hook" para asignar automáticamente el cliente al crear la plantilla
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plantilla) {
            // Capturar el ID del usuario logueado
            $userId = Auth::id();

            // Buscar en la tabla pivot para obtener el cliente_id correspondiente al usuario
            $clienteUser = ClienteUser::where('user_id', $userId)->first();

            if ($clienteUser) {
                $plantilla->id_cliente = $clienteUser->cliente_id; // Asignar el cliente_id al nuevo registro de Plantilla
            } else {
                throw new \Exception('No se pudo encontrar un cliente asociado a este usuario.');
            }
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }
}
