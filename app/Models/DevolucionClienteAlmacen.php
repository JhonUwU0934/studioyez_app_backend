<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionClienteAlmacen extends Model
{
    protected $fillable = [
        'cantidad',
        'precio_venta',
        'codigo',
        'cliente',
        'fecha',
        'quien_recibe',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }


    public function vendedor()
    {
        return $this->belongsTo(User::class, 'quien_recibe');
    }
}
