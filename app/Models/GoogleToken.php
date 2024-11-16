<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
