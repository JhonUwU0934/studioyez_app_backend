<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = [
        'ventas_diarias',
        'gastos_diarios',
        'cantidad_ventas',
        'monto_inicial',
        'total',
    ];
}
