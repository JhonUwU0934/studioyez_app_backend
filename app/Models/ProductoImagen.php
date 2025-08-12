<?php
// app/Models/ProductoImagen.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';
    
    protected $fillable = [
        'producto_id',
        'imagen',
        'alt_text',
        'orden',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
