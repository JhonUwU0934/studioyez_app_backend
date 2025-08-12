<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    protected $table = 'producto_variantes';
    
    protected $fillable = [
        'producto_id',
        'color_id',
        'talla_id',
        'sku',
        'existente_en_almacen',
        'precio_por_mayor',
        'precio_por_unidad',
        'imagen_variante',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function talla()
    {
        return $this->belongsTo(Talla::class);
    }
}