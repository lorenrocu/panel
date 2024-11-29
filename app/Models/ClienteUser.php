<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteUser extends Model
{
    use HasFactory;

    protected $table = 'cliente_user'; // Asegúrate de especificar la tabla pivot

    public $timestamps = false; // La tabla pivot no tiene columnas `created_at` ni `updated_at`

    protected $fillable = ['user_id', 'cliente_id'];

    // Definir las relaciones hacia User y Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
