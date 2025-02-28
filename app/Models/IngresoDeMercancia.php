<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngresoDeMercancia extends Model
{
    protected $fillable = [
        'producto_id',
        'fecha',
        'cantidad_de_ingreso',
        'codigo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
