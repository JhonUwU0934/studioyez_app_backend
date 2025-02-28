<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'codigo',
        'denominacion',
        'imagen',
        'existente_en_almacen',
        'precio_por_mayor',
        'precio_por_unidad',
    ];

    public function inventario()
    {
        return $this->hasOne(Inventario::class);
    }

    public function ingresosDeMercancia()
    {
        return $this->hasMany(IngresoDeMercancia::class);
    }

    public function ventas()
    {
        return $this->hasMany(Ventas::class);
    }

    public function devolucionesClienteAlmacen()
    {
        return $this->hasMany(DevolucionClienteAlmacen::class);
    }

    public function devolucionesAlmacenFabrica()
    {
        return $this->hasMany(DevolucionAlmacenFabrica::class);
    }

    public function ventaProducto()
    {
        return $this->belongsTo(VentaProducto::class, 'id_producto', 'id')->withTimestamps();
    }

}
