<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_cliente',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente', 'id_cliente');
    }

    protected $dates = [
        'expires_at',
    ];

    public $timestamps = true;
}
