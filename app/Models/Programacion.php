<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Programacion extends Model
{
    use HasFactory;

    // Si la tabla tiene un nombre diferente al plural del modelo, puedes especificarlo aquí.
    protected $table = 'programaciones';

    // Los campos que pueden ser asignados masivamente.
    protected $fillable = [
        'tipo',
        'fecha_programada',
        'estado',
        'id_cliente', // Asegúrate de agregar este campo en el fillable
    ];

    public function segmento()
    {
        return $this->belongsTo(Segmento::class, 'segmento_id', 'id');
    }


    public function getEstadoTextAttribute()
    {
        return $this->estado == 0 ? 'Pendiente' : 'Procesado';
    }

    // Si usas timestamps en la base de datos (created_at y updated_at), esta línea es opcional.
    public $timestamps = true;

    // Opcional: puedes definir la mutación de fecha si es necesario, por ejemplo para formato
    protected $casts = [
        'fecha_programada' => 'datetime', // Cast para el tipo datetime
        'estado' => 'boolean', // Cast para que el estado se trate como booleano
    ];

    // Relación con el modelo Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    protected static function boot()
    {
        parent::boot();

        // Evento antes de crear una programación
        static::creating(function ($programacion) {
            // Obtener el usuario logueado
            $userId = Auth::id();

            // Buscar el cliente asociado al usuario logueado en la tabla cliente_user
            $clienteUser = \App\Models\ClienteUser::where('user_id', $userId)->first();

            if ($clienteUser) {
                // Asignamos el id_cliente de la relación ClienteUser
                $programacion->id_cliente = $clienteUser->cliente_id; // Utilizamos 'id_cliente', no 'cliente_id'
            } else {
                throw new \Exception('No se pudo encontrar un cliente asociado al usuario logueado.');
            }
        });
    }
}
