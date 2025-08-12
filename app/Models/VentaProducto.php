<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class VentaProducto extends Pivot
{
    protected $table = 'venta_producto';
    
    // Permitir incrementos automáticos en tabla pivot
    public $incrementing = true;
    
    protected $fillable = [
        'id_venta',
        'id_producto',
        'id_producto_variante',
        'cantidad',
        'total_producto',
        'descuento',
        'sku_vendido',
        'precio_unitario_vendido',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'total_producto' => 'decimal:2',
        'descuento' => 'decimal:2',
        'precio_unitario_vendido' => 'decimal:2',
    ];

    // RELACIONES PRINCIPALES
    public function venta()
    {
        return $this->belongsTo(Ventas::class, 'id_venta');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'id_producto_variante');
    }

    // RELACIONES THROUGH PARA ACCESO DIRECTO
    public function color()
    {
        return $this->hasOneThrough(
            Color::class,
            ProductoVariante::class,
            'id', // Foreign key en producto_variantes
            'id', // Foreign key en colores
            'id_producto_variante', // Local key en venta_producto
            'color_id' // Local key en producto_variantes
        );
    }

    public function talla()
    {
        return $this->hasOneThrough(
            Talla::class,
            ProductoVariante::class,
            'id', // Foreign key en producto_variantes
            'id', // Foreign key en tallas
            'id_producto_variante', // Local key en venta_producto
            'talla_id' // Local key en producto_variantes
        );
    }

    // ATTRIBUTES CALCULADOS
    public function getSubtotalAttribute()
    {
        return $this->total_producto + $this->descuento;
    }

    public function getPrecioUnitarioOriginalAttribute()
    {
        return $this->cantidad > 0 ? $this->subtotal / $this->cantidad : 0;
    }

    public function getPorcentajeDescuentoAttribute()
    {
        return $this->subtotal > 0 ? ($this->descuento / $this->subtotal) * 100 : 0;
    }

    // MÉTODOS ÚTILES
    public function tieneDescuento()
    {
        return $this->descuento > 0;
    }

    public function tieneVariante()
    {
        return !is_null($this->id_producto_variante);
    }

    public function getInformacionCompleta()
    {
        $this->load(['producto', 'variante.color', 'variante.talla']);
        
        return [
            'id' => $this->id,
            'producto' => [
                'id' => $this->producto->id,
                'codigo' => $this->producto->codigo,
                'denominacion' => $this->producto->denominacion,
                'imagen' => $this->producto->imagen,
            ],
            'variante' => $this->variante ? [
                'id' => $this->variante->id,
                'sku' => $this->variante->sku,
                'imagen' => $this->variante->imagen_variante,
                'stock_actual' => $this->variante->existente_en_almacen,
                'color' => $this->variante->color ? [
                    'id' => $this->variante->color->id,
                    'nombre' => $this->variante->color->nombre,
                    'codigo_hex' => $this->variante->color->codigo_hex ?? null,
                ] : null,
                'talla' => $this->variante->talla ? [
                    'id' => $this->variante->talla->id,
                    'nombre' => $this->variante->talla->nombre,
                    'orden' => $this->variante->talla->orden,
                ] : null,
            ] : null,
            'venta_info' => [
                'cantidad' => $this->cantidad,
                'precio_unitario_vendido' => $this->precio_unitario_vendido,
                'subtotal' => $this->subtotal,
                'descuento' => $this->descuento,
                'total_producto' => $this->total_producto,
                'porcentaje_descuento' => round($this->porcentaje_descuento, 2),
                'sku_vendido' => $this->sku_vendido,
            ]
        ];
    }

    // SCOPES
    public function scopeConVariante($query)
    {
        return $query->whereNotNull('id_producto_variante');
    }

    public function scopeSinVariante($query)
    {
        return $query->whereNull('id_producto_variante');
    }

    public function scopeConDescuento($query)
    {
        return $query->where('descuento', '>', 0);
    }

    public function scopeDeProducto($query, $productoId)
    {
        return $query->where('id_producto', $productoId);
    }

    public function scopeDeVariante($query, $varianteId)
    {
        return $query->where('id_producto_variante', $varianteId);
    }

    public function scopeDelColor($query, $colorId)
    {
        return $query->whereHas('variante', function($q) use ($colorId) {
            $q->where('color_id', $colorId);
        });
    }

    public function scopeDeLaTalla($query, $tallaId)
    {
        return $query->whereHas('variante', function($q) use ($tallaId) {
            $q->where('talla_id', $tallaId);
        });
    }

    // EVENTOS DEL MODELO
    protected static function boot()
    {
        parent::boot();

        // Antes de crear, validar que los datos sean consistentes
        static::creating(function ($ventaProducto) {
            // Validar que existe la variante si se especifica
            if ($ventaProducto->id_producto_variante) {
                $variante = ProductoVariante::find($ventaProducto->id_producto_variante);
                if (!$variante || $variante->producto_id != $ventaProducto->id_producto) {
                    throw new \Exception('La variante no pertenece al producto especificado');
                }
            }
        });

        // Después de crear, actualizar información de auditoría
        static::created(function ($ventaProducto) {
            // Si no se guardó el SKU, intentar obtenerlo de la variante
            if (!$ventaProducto->sku_vendido && $ventaProducto->variante) {
                $ventaProducto->update([
                    'sku_vendido' => $ventaProducto->variante->sku,
                    'precio_unitario_vendido' => $ventaProducto->variante->precio_por_unidad
                ]);
            }
        });
    }
}