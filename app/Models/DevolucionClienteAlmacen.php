<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionClienteAlmacen extends Model
{
    protected $fillable = [
        'producto_id',
        'producto_variante_id',
        'cantidad',
        'precio_venta',
        'codigo',
        'cliente',
        'fecha',
        'quien_recibe',
    ];

    public function productoVariante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }


    public function vendedor()
    {
        return $this->belongsTo(User::class, 'quien_recibe');
    }
}
