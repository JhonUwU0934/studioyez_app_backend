<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected $casts = [
        'fecha' => 'date',
        'precio_venta' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
        'precio_unidad' => 'decimal:2',
    ];

    // RELACIÓN CON VENDEDOR (si existe tabla users)
    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor');
    }

    // RELACIÓN DIRECTA CON TABLA PIVOT venta_producto
    public function ventaProductos()
    {
        return $this->hasMany(VentaProducto::class, 'id_venta');
    }

    // RELACIÓN CON PRODUCTOS A TRAVÉS DE LA TABLA PIVOT (MEJORADA)
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'venta_producto', 'id_venta', 'id_producto')
            ->withPivot([
                'id',
                'id_producto_variante',
                'cantidad', 
                'total_producto', 
                'descuento',
                'sku_vendido',
                'precio_unitario_vendido',
                'created_at',
                'updated_at'
            ])
            ->using(VentaProducto::class);
    }

    // NUEVA RELACIÓN: OBTENER PRODUCTOS CON INFORMACIÓN COMPLETA DE VARIANTES
    public function productosConVariantes()
    {
        return $this->ventaProductos()
            ->with([
                'producto',
                'variante' => function($query) {
                    $query->with(['color', 'talla']);
                }
            ]);
    }

    // MÉTODO PARA OBTENER INFORMACIÓN DETALLADA DE LA VENTA
    public function getProductosDetalladosAttribute()
    {
        return DB::table('venta_producto')
            ->join('productos', 'venta_producto.id_producto', '=', 'productos.id')
            ->leftJoin('producto_variantes', 'venta_producto.id_producto_variante', '=', 'producto_variantes.id')
            ->leftJoin('colores', 'producto_variantes.color_id', '=', 'colores.id')
            ->leftJoin('tallas', 'producto_variantes.talla_id', '=', 'tallas.id')
            ->where('venta_producto.id_venta', $this->id)
            ->select([
                // Información del producto
                'productos.id as producto_id',
                'productos.codigo',
                'productos.denominacion',
                'productos.imagen as producto_imagen',
                
                // Información de la venta
                'venta_producto.cantidad',
                'venta_producto.total_producto',
                'venta_producto.descuento',
                'venta_producto.sku_vendido',
                'venta_producto.precio_unitario_vendido',
                
                // Información de la variante
                'producto_variantes.id as variante_id',
                'producto_variantes.sku as variante_sku',
                'producto_variantes.imagen_variante',
                'producto_variantes.existente_en_almacen as stock_actual',
                
                // Información del color
                'colores.id as color_id',
                'colores.nombre as color_nombre',
                'colores.codigo_hex as color_codigo',
                
                // Información de la talla
                'tallas.id as talla_id',
                'tallas.nombre as talla_nombre',
                'tallas.orden as talla_orden',
            ])
            ->get()
            ->map(function ($item) {
                return [
                    'producto' => [
                        'id' => $item->producto_id,
                        'codigo' => $item->codigo,
                        'denominacion' => $item->denominacion,
                        'imagen' => $item->producto_imagen,
                    ],
                    'venta' => [
                        'cantidad' => (int) $item->cantidad,
                        'total_producto' => (float) $item->total_producto,
                        'descuento' => (float) $item->descuento,
                        'precio_unitario_vendido' => (float) $item->precio_unitario_vendido,
                        'sku_vendido' => $item->sku_vendido,
                    ],
                    'variante' => [
                        'id' => $item->variante_id,
                        'sku' => $item->variante_sku,
                        'imagen' => $item->imagen_variante,
                        'stock_actual' => $item->stock_actual,
                        'color' => $item->color_id ? [
                            'id' => $item->color_id,
                            'nombre' => $item->color_nombre,
                            'codigo_hex' => $item->color_codigo,
                        ] : null,
                        'talla' => $item->talla_id ? [
                            'id' => $item->talla_id,
                            'nombre' => $item->talla_nombre,
                            'orden' => $item->talla_orden,
                        ] : null,
                    ],
                ];
            });
    }

    // MÉTODOS AUXILIARES ÚTILES

    // Obtener el total de productos vendidos (cantidad)
    public function getTotalProductosAttribute()
    {
        return $this->ventaProductos()->sum('cantidad');
    }

    // Obtener número de productos diferentes
    public function getCantidadProductosDiferentesAttribute()
    {
        return $this->ventaProductos()->count();
    }

    // Obtener total de descuentos aplicados
    public function getTotalDescuentosAttribute()
    {
        return $this->ventaProductos()->sum('descuento');
    }

    // Verificar si la venta tiene variantes
    public function tieneVariantes()
    {
        return $this->ventaProductos()->whereNotNull('id_producto_variante')->exists();
    }

    // Obtener colores vendidos
    public function getColoresVendidos()
    {
        return $this->ventaProductos()
            ->with('variante.color')
            ->whereHas('variante.color')
            ->get()
            ->pluck('variante.color')
            ->unique('id')
            ->values();
    }

    // Obtener tallas vendidas
    public function getTallasVendidas()
    {
        return $this->ventaProductos()
            ->with('variante.talla')
            ->whereHas('variante.talla')
            ->get()
            ->pluck('variante.talla')
            ->unique('id')
            ->sortBy('orden')
            ->values();
    }

    // Verificar si se vendió una variante específica
    public function seVendioVariante($varianteId)
    {
        return $this->ventaProductos()
            ->where('id_producto_variante', $varianteId)
            ->exists();
    }

    // Obtener cantidad vendida de una variante específica
    public function getCantidadVariante($varianteId)
    {
        return $this->ventaProductos()
            ->where('id_producto_variante', $varianteId)
            ->sum('cantidad');
    }

    // SCOPES ÚTILES

    public function scopeConVariantes($query)
    {
        return $query->whereHas('ventaProductos', function($q) {
            $q->whereNotNull('id_producto_variante');
        });
    }

    public function scopeDelColor($query, $colorId)
    {
        return $query->whereHas('ventaProductos.variante', function($q) use ($colorId) {
            $q->where('color_id', $colorId);
        });
    }

    public function scopeDeLaTalla($query, $tallaId)
    {
        return $query->whereHas('ventaProductos.variante', function($q) use ($tallaId) {
            $q->where('talla_id', $tallaId);
        });
    }

    public function scopeDelPeriodo($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    public function scopeDelVendedor($query, $vendedor)
    {
        return $query->where('vendedor', $vendedor);
    }

    // MÉTODO PARA GENERAR RESUMEN DE VENTA
    public function generarResumen()
    {
        $productos = $this->productos_detallados;
        
        return [
            'venta' => [
                'id' => $this->id,
                'codigo_factura' => $this->codigo_factura,
                'fecha' => $this->fecha->format('Y-m-d'),
                'cliente' => $this->nombre_comprador,
                'telefono' => $this->numero_comprador,
                'vendedor' => $this->vendedor,
                'estado_pago' => $this->estado_pago,
            ],
            'totales' => [
                'productos_diferentes' => $this->cantidad_productos_diferentes,
                'cantidad_total' => $this->total_productos,
                'subtotal' => $productos->sum('venta.total_producto') + $this->total_descuentos,
                'descuentos' => $this->total_descuentos,
                'total_final' => $this->precio_venta,
            ],
            'productos' => $productos,
            'variantes_resumen' => [
                'colores' => $this->getColoresVendidos(),
                'tallas' => $this->getTallasVendidas(),
            ]
        ];
    }
}