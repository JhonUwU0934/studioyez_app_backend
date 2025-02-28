<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionAlmacenFabrica extends Model
{
    protected $fillable = [
        'cantidad',
        'fecha',
        'quien_entrega',
        'quien_recibe',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
