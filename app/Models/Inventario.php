<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $fillable = [
        'cantidad_contada',
        'cantidad_sin_referencia',
        'hoja',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
