<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ventas extends Model
{
    protected $fillable = [
        'codigo_factura',
        'cantidad',
        'fecha',
        'precio_mayorista',
        'precio_unidad',
        'precio_venta',
        'estado_pago',
        'vendedor',
        'nombre_comprador',
        'numero_comprador',
        'url_factura'
    ];

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function ventaProducto()
    {
        return $this->belongsTo(VentaProducto::class, 'id_venta', 'id')->withTimestamps();
    }
}
