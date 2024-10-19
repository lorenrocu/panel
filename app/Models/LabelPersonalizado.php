<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabelPersonalizado extends Model
{
    use HasFactory;

    protected $table = 'labels_personalizado';

    protected $fillable = [
        'id_account',
        'valor_label',
        'id_cliente',
    ];
}
