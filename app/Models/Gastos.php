<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gastos extends Model
{
    use HasFactory;
    protected $fillable = [
        'descripcion',
        'monto',
        'fecha',
        'created_at',
        'updated_at',
    ];
}
