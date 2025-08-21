<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngresoDeMercancia extends Model
{
    protected $table = 'ingreso_de_mercancias';
    
    protected $fillable = [
        'producto_id',
        'producto_variante_id',
        'fecha',
        'cantidad_de_ingreso',
        'codigo',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function productoVariante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    // Método para obtener el producto correcto (directo o a través de variante)
    public function getProductoRelacionadoAttribute()
    {
        if ($this->producto_variante_id && $this->productoVariante) {
            return $this->productoVariante->producto;
        }
        return $this->producto;
    }

    // Método para obtener el nombre completo del item (producto + variante si existe)
    public function getNombreCompletoAttribute()
    {
        $productoBase = $this->producto_relacionado;
        if (!$productoBase) return 'Producto no encontrado';
        
        $nombre = $productoBase->denominacion;
        
        if ($this->producto_variante_id && $this->productoVariante) {
            $detalles = [];
            if ($this->productoVariante->color) {
                $detalles[] = $this->productoVariante->color->nombre;
            }
            if ($this->productoVariante->talla) {
                $detalles[] = $this->productoVariante->talla->nombre;
            }
            if (!empty($detalles)) {
                $nombre .= ' (' . implode(' - ', $detalles) . ')';
            }
        }
        
        return $nombre;
    }

    // Método para obtener el SKU si existe
    public function getSkuMostrarAttribute()
    {
        if ($this->producto_variante_id && $this->productoVariante && $this->productoVariante->sku) {
            return $this->productoVariante->sku;
        }
        
        $productoBase = $this->producto_relacionado;
        return $productoBase ? $productoBase->codigo : 'Sin código';
    }

    // Método para determinar si es ingreso de variante o producto base
    public function esIngresoDeVarianteAttribute()
    {
        return !is_null($this->producto_variante_id);
    }

    // Método para obtener el stock actual del item
    public function getStockActualAttribute()
    {
        if ($this->es_ingreso_de_variante && $this->productoVariante) {
            return $this->productoVariante->existente_en_almacen;
        }
        
        $productoBase = $this->producto_relacionado;
        return $productoBase ? ($productoBase->existente_en_almacen ?? 0) : 0;
    }
}