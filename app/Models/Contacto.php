<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ClienteUser;
use App\Models\Segmento;

class Contacto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'celular',
        'segmento',
        'cliente_id',
    ];

    // Hook para asignar automáticamente el cliente al crear el contacto
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contacto) {
            // Capturar el ID del usuario logueado
            $userId = Auth::id();

            // Buscar en la tabla pivot para obtener el cliente_id correspondiente al usuario
            $clienteUser = ClienteUser::where('user_id', $userId)->first();

            if ($clienteUser) {
                // Asignar el cliente_id al nuevo registro de Contacto
                $contacto->cliente_id = $clienteUser->cliente_id;
            } else {
                throw new \Exception('No se pudo encontrar un cliente asociado a este usuario.');
            }
        });
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function segmento()
    {
        return $this->belongsToMany(
            \App\Models\Segmento::class,    // El modelo relacionado
            'contacto_segmento',            // La tabla pivote
            'contacto_id',                  // La columna en pivote que hace referencia a 'contactos'
            'segmento_id'                   // La columna en pivote que hace referencia a 'segmentos'
        );
    }
}
