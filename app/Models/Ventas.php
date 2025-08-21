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
        'url_factura',
        'valor_efectivo',        // ✅ NUEVO
        'valor_transferencia',   // ✅ NUEVO
        'valor_credito',         // ✅ NUEVO
        'credito_pagado',        // ✅ NUEVO
        'fecha_credito',         // ✅ NUEVO
        'fecha_promesa_pago',    // ✅ NUEVO
        'valor_devuelto',        // ✅ NUEVO
    ];

    protected $casts = [
        'fecha' => 'date',
        'precio_venta' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
        'precio_unidad' => 'decimal:2',
        'valor_efectivo' => 'decimal:2',        // ✅ NUEVO
        'valor_transferencia' => 'decimal:2',   // ✅ NUEVO
        'valor_credito' => 'decimal:2',         // ✅ NUEVO
        // 'credito_pagado' => 'boolean',       // ✅ REMOVIDO: Usamos NULL/0/1 manualmente
        'fecha_credito' => 'date',              // ✅ NUEVO
        'fecha_promesa_pago' => 'date',         // ✅ NUEVO
        'valor_devuelto' => 'decimal:2',        // ✅ NUEVO
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

    // ✅ NUEVOS MÉTODOS PARA PAGO COMBINADO + CRÉDITO

    // Obtener el total pagado combinando efectivo, transferencia y crédito
    public function getTotalPagadoAttribute()
    {
        return (float) $this->valor_efectivo + (float) $this->valor_transferencia + (float) $this->valor_credito;
    }

    // Obtener solo el total pagado en efectivo + transferencia (sin crédito)
    public function getTotalPagadoEfectivoAttribute()
    {
        return (float) $this->valor_efectivo + (float) $this->valor_transferencia;
    }

    // Verificar si el pago fue solo en efectivo
    public function esPagoSoloEfectivo()
    {
        return $this->valor_efectivo > 0 && $this->valor_transferencia == 0;
    }

    // Verificar si el pago fue solo por transferencia
    public function esPagoSoloTransferencia()
    {
        return $this->valor_transferencia > 0 && $this->valor_efectivo == 0 && $this->valor_credito == 0;
    }

    // Verificar si el pago fue combinado
    public function esPagoCombinado()
    {
        $metodosPago = 0;
        if ($this->valor_efectivo > 0) $metodosPago++;
        if ($this->valor_transferencia > 0) $metodosPago++;
        if ($this->valor_credito > 0) $metodosPago++;
        
        return $metodosPago > 1;
    }

    // ✅ NUEVOS MÉTODOS PARA CRÉDITO CON LÓGICA NULL/0/1

    // Verificar si la venta tiene crédito
    public function tieneCredito()
    {
        return !is_null($this->valor_credito) && $this->valor_credito > 0;
    }

    // Verificar si el crédito está pagado
    public function creditoPagado()
    {
        return $this->credito_pagado === 1;
    }

    // Verificar si el crédito está pendiente
    public function creditoPendiente()
    {
        return $this->tieneCredito() && $this->credito_pagado === 0;
    }

    // Verificar si NO tiene crédito
    public function sinCredito()
    {
        return is_null($this->credito_pagado);
    }

    // Verificar si el crédito está vencido
    public function creditoVencido()
    {
        if (!$this->tieneCredito() || $this->creditoPagado()) {
            return false;
        }
        
        return $this->fecha_promesa_pago && \Carbon\Carbon::parse($this->fecha_promesa_pago)->isPast();
    }

    // Obtener días de vencimiento del crédito
    public function getDiasVencimientoAttribute()
    {
        if (!$this->tieneCredito() || $this->creditoPagado()) {
            return 0;
        }
        
        if (!$this->fecha_promesa_pago) {
            return 0;
        }
        
        return \Carbon\Carbon::parse($this->fecha_promesa_pago)->diffInDays(now(), false);
    }

    // Obtener días restantes para vencimiento
    public function getDiasRestantesAttribute()
    {
        return max(0, -$this->dias_vencimiento);
    }

    // Obtener el tipo de pago como string
    public function getTipoPagoAttribute()
    {
        $metodos = [];
        
        if ($this->valor_efectivo > 0) $metodos[] = 'efectivo';
        if ($this->valor_transferencia > 0) $metodos[] = 'transferencia';
        if ($this->valor_credito > 0) $metodos[] = 'crédito';
        
        if (empty($metodos)) {
            return 'no_definido';
        } elseif (count($metodos) == 1) {
            return $metodos[0];
        } else {
            return 'combinado';
        }
    }

    // Obtener porcentaje de efectivo del pago total
    public function getPorcentajeEfectivoAttribute()
    {
        $totalPagado = $this->total_pagado;
        return $totalPagado > 0 ? ($this->valor_efectivo / $totalPagado) * 100 : 0;
    }

    // Obtener porcentaje de transferencia del pago total
    public function getPorcentajeTransferenciaAttribute()
    {
        $totalPagado = $this->total_pagado;
        return $totalPagado > 0 ? ($this->valor_transferencia / $totalPagado) * 100 : 0;
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

    // ✅ NUEVOS SCOPES PARA PAGO COMBINADO + CRÉDITO

    public function scopePagoEfectivo($query)
    {
        return $query->where('valor_efectivo', '>', 0)
                    ->where('valor_transferencia', 0)
                    ->where('valor_credito', 0);
    }

    public function scopePagoTransferencia($query)
    {
        return $query->where('valor_transferencia', '>', 0)
                    ->where('valor_efectivo', 0)
                    ->where('valor_credito', 0);
    }

    public function scopePagoCredito($query)
    {
        return $query->where('valor_credito', '>', 0)
                    ->where('valor_efectivo', 0)
                    ->where('valor_transferencia', 0);
    }

    public function scopePagoCombinado($query)
    {
        return $query->where(function($q) {
            $q->where(function($subQ) {
                $subQ->where('valor_efectivo', '>', 0)->where('valor_transferencia', '>', 0);
            })->orWhere(function($subQ) {
                $subQ->where('valor_efectivo', '>', 0)->where('valor_credito', '>', 0);
            })->orWhere(function($subQ) {
                $subQ->where('valor_transferencia', '>', 0)->where('valor_credito', '>', 0);
            });
        });
    }

    // ✅ NUEVOS SCOPES PARA CRÉDITO CON LÓGICA NULL/0/1

    public function scopeConCredito($query)
    {
        return $query->whereNotNull('valor_credito')->where('valor_credito', '>', 0);
    }

    public function scopeSinCredito($query)
    {
        return $query->whereNull('credito_pagado');
    }

    public function scopeCreditoPendiente($query)
    {
        return $query->whereNotNull('valor_credito')
                    ->where('valor_credito', '>', 0)
                    ->where('credito_pagado', 0);
    }

    public function scopeCreditoPagado($query)
    {
        return $query->whereNotNull('valor_credito')
                    ->where('valor_credito', '>', 0)
                    ->where('credito_pagado', 1);
    }

    public function scopeCreditoVencido($query)
    {
        return $query->whereNotNull('valor_credito')
                    ->where('valor_credito', '>', 0)
                    ->where('credito_pagado', 0)
                    ->where('fecha_promesa_pago', '<', now());
    }

    public function scopeCreditoPorVencer($query, $dias = 3)
    {
        $fechaLimite = now()->addDays($dias);
        return $query->whereNotNull('valor_credito')
                    ->where('valor_credito', '>', 0)
                    ->where('credito_pagado', 0)
                    ->whereBetween('fecha_promesa_pago', [now(), $fechaLimite]);
    }

    public function scopeConCambio($query)
    {
        return $query->where('valor_devuelto', '>', 0);
    }

    public function scopeRangoEfectivo($query, $minimo, $maximo)
    {
        return $query->whereBetween('valor_efectivo', [$minimo, $maximo]);
    }

    public function scopeRangoTransferencia($query, $minimo, $maximo)
    {
        return $query->whereBetween('valor_transferencia', [$minimo, $maximo]);
    }

    // MÉTODO PARA GENERAR RESUMEN DE VENTA COMPLETO
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
            // ✅ NUEVO: Información de pago combinado + crédito
            'pago' => [
                'tipo_pago' => $this->tipo_pago,
                'valor_efectivo' => $this->valor_efectivo,
                'valor_transferencia' => $this->valor_transferencia,
                'valor_credito' => $this->valor_credito,
                'total_pagado' => $this->total_pagado,
                'total_pagado_efectivo' => $this->total_pagado_efectivo,
                'valor_devuelto' => $this->valor_devuelto,
                'porcentaje_efectivo' => round($this->porcentaje_efectivo, 2),
                'porcentaje_transferencia' => round($this->porcentaje_transferencia, 2),
                'es_pago_combinado' => $this->esPagoCombinado(),
                'es_solo_efectivo' => $this->esPagoSoloEfectivo(),
                'es_solo_transferencia' => $this->esPagoSoloTransferencia(),
                'tiene_credito' => $this->tieneCredito(),
            ],
            // ✅ NUEVO: Información de crédito
            'credito' => $this->tieneCredito() ? [
                'valor_credito' => $this->valor_credito,
                'credito_pagado' => $this->creditoPagado(),
                'credito_pendiente' => $this->creditoPendiente(),
                'credito_vencido' => $this->creditoVencido(),
                'fecha_credito' => $this->fecha_credito?->format('Y-m-d'),
                'fecha_promesa_pago' => $this->fecha_promesa_pago?->format('Y-m-d'),
                'dias_vencimiento' => $this->dias_vencimiento,
                'dias_restantes' => $this->dias_restantes,
            ] : null,
            'productos' => $productos,
            'variantes_resumen' => [
                'colores' => $this->getColoresVendidos(),
                'tallas' => $this->getTallasVendidas(),
            ]
        ];
    }

    // ✅ MÉTODO PARA OBTENER ESTADÍSTICAS DE PAGO + CRÉDITO
    public function getEstadisticasPago()
    {
        $estadisticas = [
            'efectivo' => [
                'valor' => $this->valor_efectivo,
                'porcentaje' => $this->porcentaje_efectivo,
                'es_principal' => $this->valor_efectivo >= max($this->valor_transferencia, $this->valor_credito),
            ],
            'transferencia' => [
                'valor' => $this->valor_transferencia,
                'porcentaje' => $this->porcentaje_transferencia,
                'es_principal' => $this->valor_transferencia >= max($this->valor_efectivo, $this->valor_credito),
            ],
            'cambio' => [
                'valor' => $this->valor_devuelto,
                'hubo_cambio' => $this->valor_devuelto > 0,
            ],
            'resumen' => [
                'total_venta' => $this->precio_venta,
                'total_pagado' => $this->total_pagado,
                'total_pagado_efectivo' => $this->total_pagado_efectivo,
                'tipo_pago' => $this->tipo_pago,
                'pago_exacto' => $this->valor_devuelto == 0,
            ]
        ];

        // Agregar información de crédito si existe
        if ($this->tieneCredito()) {
            $estadisticas['credito'] = [
                'valor' => $this->valor_credito,
                'porcentaje' => $this->total_pagado > 0 ? ($this->valor_credito / $this->total_pagado) * 100 : 0,
                'es_principal' => $this->valor_credito >= max($this->valor_efectivo, $this->valor_transferencia),
                'estado' => $this->creditoPagado() ? 'pagado' : 'pendiente',
                'vencido' => $this->creditoVencido(),
                'dias_vencimiento' => $this->dias_vencimiento,
                'fecha_promesa' => $this->fecha_promesa_pago?->format('Y-m-d'),
            ];
        }

        return $estadisticas;
    }

    // ✅ NUEVO: Método para obtener estado del crédito con colores
    public function getEstadoCredito()
    {
        if (!$this->tieneCredito()) {
            return [
                'estado' => 'sin_credito',
                'descripcion' => 'Sin crédito',
                'color' => 'secondary',
                'icono' => 'fa-minus'
            ];
        }

        if ($this->creditoPagado()) {
            return [
                'estado' => 'pagado',
                'descripcion' => 'Crédito Pagado',
                'color' => 'success',
                'icono' => 'fa-check-circle'
            ];
        }

        if ($this->creditoVencido()) {
            return [
                'estado' => 'vencido',
                'descripcion' => 'Crédito Vencido',
                'color' => 'danger',
                'icono' => 'fa-exclamation-triangle',
                'dias_vencido' => $this->dias_vencimiento
            ];
        }

        if ($this->dias_restantes <= 3) {
            return [
                'estado' => 'por_vencer',
                'descripcion' => 'Por Vencer',
                'color' => 'warning',
                'icono' => 'fa-clock',
                'dias_restantes' => $this->dias_restantes
            ];
        }

        return [
            'estado' => 'vigente',
            'descripcion' => 'Crédito Vigente',
            'color' => 'info',
            'icono' => 'fa-calendar-check',
            'dias_restantes' => $this->dias_restantes
        ];
    }
}