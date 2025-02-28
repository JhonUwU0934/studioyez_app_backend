<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaProducto extends Model
{
    protected $fillable = [
        'id_venta',
        'id_producto',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ventas()
    {
        return $this->belongsTo(Ventas::class);
    }
}
