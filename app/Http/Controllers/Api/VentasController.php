<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\VentaProducto;
use App\Models\Ventas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\VentasResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;

class VentasController extends Controller
{
    public function index()
    {
        $ventas = Ventas::with(['vendedor', 'productos'])->latest('created_at')->get();

        return ['data' => $ventas];
    }

    public function store(Request $request)
    {
        try {
            $data = $request->json()->all();

            $nombreComprador = $data['nombre_comprador'];
            $numeroComprador = $data['numero_comprador'];
            $vendedor = $data['vendedor'];

            // ✅ NUEVO: Manejo de pago combinado + crédito
            $valorEfectivo = isset($data['valor_efectivo']) ? floatval($data['valor_efectivo']) : 0;
            $valorTransferencia = isset($data['valor_transferencia']) ? floatval($data['valor_transferencia']) : 0;
            $valorCredito = isset($data['valor_credito']) ? floatval($data['valor_credito']) : 0;
            $fechaPromesaPago = $data['fecha_promesa_pago'] ?? null;
            
            $totalPagado = $valorEfectivo + $valorTransferencia + $valorCredito;
            $valorDevuelto = 0;

            $productosData = $data['productos'];
            $totalVenta = 0;
            $error = null;

            // PRIMERA PASADA: Validar existencias antes de procesar
            foreach ($productosData as $productoData) {
                $cantidadProducto = intval($productoData['cantidad']);
                $codigoProducto = $productoData['codigo'];
                $totalProducto = floatval($productoData['total']);
                $descuentoProducto = floatval($productoData['descuento'] ?? 0);
                
                // Datos de variante (obligatorios)
                $varianteId = $productoData['variante_id'] ?? null;
                $skuVariante = $productoData['sku_variante'] ?? null;
                
                // Validar que se envíe información de variante
                if (!$varianteId && !$skuVariante) {
                    $error = "Debe especificar 'variante_id' o 'sku_variante' para el producto con código '{$codigoProducto}'";
                    break;
                }
                
                $producto = Producto::where('codigo', $codigoProducto)->first();

                if (!$producto) {
                    $error = "Producto con código '{$codigoProducto}' no encontrado";
                    break;
                }

                // Buscar variante específica
                $variante = null;
                if ($varianteId) {
                    $variante = ProductoVariante::where('id', $varianteId)
                        ->where('producto_id', $producto->id)
                        ->where('activo', true)
                        ->first();
                } elseif ($skuVariante) {
                    $variante = ProductoVariante::where('sku', $skuVariante)
                        ->where('producto_id', $producto->id)
                        ->where('activo', true)
                        ->first();
                }

                if (!$variante) {
                    $identifier = $varianteId ? "ID: {$varianteId}" : "SKU: {$skuVariante}";
                    $error = "Variante ({$identifier}) no encontrada o inactiva para el producto '{$producto->denominacion}' (Código: {$producto->codigo})";
                    break;
                }

                // Verificar existencia en la variante específica
                if ($variante->existente_en_almacen < $cantidadProducto) {
                    $varianteInfo = "";
                    if ($variante->color) $varianteInfo .= "Color: {$variante->color->nombre} ";
                    if ($variante->talla) $varianteInfo .= "Talla: {$variante->talla->nombre} ";
                    if ($variante->sku) $varianteInfo .= "SKU: {$variante->sku}";
                    
                    $error = "Stock insuficiente para la variante del producto '{$producto->denominacion}' ({$varianteInfo}). Disponible: {$variante->existente_en_almacen}, solicitado: {$cantidadProducto}";
                    break;
                }

                $totalVenta += $totalProducto;
            }

            if ($error) {
                return response()->json([
                    'success' => false,
                    'error' => $error
                ], 400);
            }

            // ✅ NUEVO: Validaciones para crédito
            if ($valorCredito > 0) {
                if (!$fechaPromesaPago) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Debe especificar la fecha de promesa de pago para ventas a crédito'
                    ], 400);
                }
                
                // Validar que la fecha de promesa sea futura
                $fechaPromesa = \Carbon\Carbon::parse($fechaPromesaPago);
                if ($fechaPromesa->isPast()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'La fecha de promesa de pago debe ser una fecha futura'
                    ], 400);
                }
                
                // Validar que no sea más de 6 meses (opcional)
                if ($fechaPromesa->diffInMonths(now()) > 6) {
                    return response()->json([
                        'success' => false,
                        'error' => 'El crédito no puede exceder 6 meses'
                    ], 400);
                }
            }

            // ✅ NUEVO: Validar que el total pagado sea suficiente (incluyendo crédito)
            if ($totalPagado < $totalVenta) {
                return response()->json([
                    'success' => false,
                    'error' => "El total comprometido ($" . number_format($totalPagado, 2) . ") es menor al total de la venta ($" . number_format($totalVenta, 2) . "). Falta: $" . number_format($totalVenta - $totalPagado, 2)
                ], 400);
            }

            // ✅ NUEVO: Calcular cambio solo del efectivo + transferencia (no del crédito)
            $pagadoEnEfectivo = $valorEfectivo + $valorTransferencia;
            if ($pagadoEnEfectivo > $totalVenta) {
                $valorDevuelto = $pagadoEnEfectivo - $totalVenta;
            }

            DB::beginTransaction();
            
            // Generar el código de venta aleatorio
            $codigoVenta = 'STDY' . date('Y') . rand(1000, 9999);

            // ✅ MODIFICADO: Crear la venta con campos de pago combinado + crédito
            $venta = Ventas::create([
                'nombre_comprador' => $nombreComprador,
                'numero_comprador' => $numeroComprador,
                'vendedor' => $vendedor,
                'codigo_factura' => $codigoVenta,
                'precio_venta' => $totalVenta,
                'fecha' => date('Y-m-d'),
                'valor_efectivo' => $valorEfectivo,
                'valor_transferencia' => $valorTransferencia,
                'valor_credito' => $valorCredito > 0 ? $valorCredito : null,
                'credito_pagado' => $valorCredito > 0 ? 0 : null, // NULL=sin crédito, 0=pendiente, 1=pagado
                'fecha_credito' => $valorCredito > 0 ? date('Y-m-d') : null,
                'fecha_promesa_pago' => $fechaPromesaPago,
                'valor_devuelto' => $valorDevuelto,
            ]);

            Log::info('Venta creada con ID: ' . $venta->id);

            // SEGUNDA PASADA: Procesar productos y descontar variantes
            foreach ($productosData as $productoData) {
                $cantidadProducto = intval($productoData['cantidad']);
                $codigoProducto = $productoData['codigo'];
                $totalProducto = floatval($productoData['total']);
                $descuentoProducto = floatval($productoData['descuento'] ?? 0);
                
                // Datos de variante
                $varianteId = $productoData['variante_id'] ?? null;
                $skuVariante = $productoData['sku_variante'] ?? null;

                $producto = Producto::where('codigo', $codigoProducto)->first();
                
                // Buscar variante (ya validada anteriormente)
                if ($varianteId) {
                    $variante = ProductoVariante::find($varianteId);
                } else {
                    $variante = ProductoVariante::where('sku', $skuVariante)
                        ->where('producto_id', $producto->id)
                        ->first();
                }

                // Insertar en tabla pivot con información de variante
                DB::table('venta_producto')->insert([
                    'id_venta' => $venta->id,
                    'id_producto' => $producto->id,
                    'id_producto_variante' => $variante->id,
                    'cantidad' => $cantidadProducto,
                    'total_producto' => $totalProducto,
                    'descuento' => $descuentoProducto,
                    'sku_vendido' => $variante->sku,
                    'precio_unitario_vendido' => $variante->precio_por_unidad,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("Producto insertado en venta_producto: Producto ID {$producto->id}, Variante ID {$variante->id}");

                // ACTUALIZAR EXISTENCIA DE LA VARIANTE
                $variante->update([
                    'existente_en_almacen' => $variante->existente_en_almacen - $cantidadProducto
                ]);

                // También actualizar existencia total del producto base
                $producto->update([
                    'existente_en_almacen' => $producto->existente_en_almacen - $cantidadProducto
                ]);

                Log::info("Stock actualizado - Variante ID {$variante->id}: {$variante->existente_en_almacen}, Producto ID {$producto->id}: {$producto->existente_en_almacen}");
            }

            DB::commit();
            
            return new VentasResource($venta);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear venta: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $venta = Ventas::with(['vendedor'])->findOrFail($id);

            // Obtener productos con información de variantes
            $productos = DB::table('productos')
                ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
                ->leftJoin('producto_variantes', 'venta_producto.id_producto_variante', '=', 'producto_variantes.id')
                ->leftJoin('colores', 'producto_variantes.color_id', '=', 'colores.id')
                ->leftJoin('tallas', 'producto_variantes.talla_id', '=', 'tallas.id')
                ->where('venta_producto.id_venta', $venta->id)
                ->select(
                    'productos.*', 
                    'venta_producto.cantidad', 
                    'venta_producto.total_producto',
                    'venta_producto.descuento',
                    'venta_producto.sku_vendido',
                    'venta_producto.precio_unitario_vendido',
                    'producto_variantes.id as variante_id',
                    'producto_variantes.sku as variante_sku',
                    'colores.nombre as color_nombre',
                    'colores.codigo_hex as color_codigo',
                    'tallas.nombre as talla_nombre',
                    'tallas.orden as talla_orden',
                    'producto_variantes.imagen_variante'
                )
                ->get();

            $venta->productos = $productos;

            return ['data' => $venta];
            
        } catch (\Exception $e) {
            Log::error('Error en show venta: ' . $e->getMessage());
            return response()->json([
                'error' => 'Venta no encontrada',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (strpos($user->name, 'Asesor') === 0) {
                return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
            }

            $data = $request->json()->all();
            $venta = Ventas::findOrFail($id);

            DB::beginTransaction();
            
            // ✅ NUEVO: Manejo de pago combinado + crédito para update
            $valorEfectivo = isset($data['valor_efectivo']) ? floatval($data['valor_efectivo']) : 0;
            $valorTransferencia = isset($data['valor_transferencia']) ? floatval($data['valor_transferencia']) : 0;
            $valorCredito = isset($data['valor_credito']) ? floatval($data['valor_credito']) : 0;
            $fechaPromesaPago = $data['fecha_promesa_pago'] ?? null;
            
            $totalPagado = $valorEfectivo + $valorTransferencia + $valorCredito;
            $valorDevuelto = 0;
            
            $productosData = $data['productos'];
            $totalVenta = 0;

            // Primero, devolver stock de productos actuales
            $productosActuales = DB::table('venta_producto')
                ->where('id_venta', $venta->id)
                ->get();
                
            foreach ($productosActuales as $productoActual) {
                // Devolver stock a la variante
                if ($productoActual->id_producto_variante) {
                    $variante = ProductoVariante::find($productoActual->id_producto_variante);
                    if ($variante) {
                        $variante->update([
                            'existente_en_almacen' => $variante->existente_en_almacen + intval($productoActual->cantidad)
                        ]);
                    }
                }
                
                // Devolver stock al producto base
                $producto = Producto::find($productoActual->id_producto);
                if ($producto) {
                    $producto->update([
                        'existente_en_almacen' => $producto->existente_en_almacen + intval($productoActual->cantidad)
                    ]);
                }
            }

            // Eliminar productos actuales de la venta
            DB::table('venta_producto')->where('id_venta', $venta->id)->delete();

            // Validar y procesar nuevos productos
            foreach ($productosData as $productoData) {
                $cantidadProducto = intval($productoData['cantidad']);
                $codigoProducto = $productoData['codigo'];
                $totalProducto = floatval($productoData['total']);
                $descuentoProducto = floatval($productoData['descuento'] ?? 0);
                
                $varianteId = $productoData['variante_id'] ?? null;
                $skuVariante = $productoData['sku_variante'] ?? null;

                $producto = Producto::where('codigo', $codigoProducto)->first();

                if ($producto) {
                    // Buscar variante
                    $variante = null;
                    if ($varianteId) {
                        $variante = ProductoVariante::where('id', $varianteId)
                            ->where('activo', true)
                            ->first();
                    } elseif ($skuVariante) {
                        $variante = ProductoVariante::where('sku', $skuVariante)
                            ->where('producto_id', $producto->id)
                            ->where('activo', true)
                            ->first();
                    }

                    if ($variante && $variante->existente_en_almacen >= $cantidadProducto) {
                        $totalVenta += $totalProducto;

                        // Insertar producto actualizado
                        DB::table('venta_producto')->insert([
                            'id_venta' => $venta->id,
                            'id_producto' => $producto->id,
                            'id_producto_variante' => $variante->id,
                            'cantidad' => $cantidadProducto,
                            'total_producto' => $totalProducto,
                            'descuento' => $descuentoProducto,
                            'sku_vendido' => $variante->sku,
                            'precio_unitario_vendido' => $variante->precio_por_unidad,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // Descontar de variante
                        $variante->update([
                            'existente_en_almacen' => $variante->existente_en_almacen - $cantidadProducto
                        ]);
                        
                        // Descontar de producto base
                        $producto->update([
                            'existente_en_almacen' => $producto->existente_en_almacen - $cantidadProducto
                        ]);
                    } else {
                        throw new \Exception("Stock insuficiente para la variante del producto '{$producto->denominacion}'");
                    }
                }
            }

            // ✅ NUEVO: Calcular cambio para update (solo efectivo + transferencia)
            $pagadoEnEfectivo = $valorEfectivo + $valorTransferencia;
            if ($pagadoEnEfectivo > $totalVenta) {
                $valorDevuelto = $pagadoEnEfectivo - $totalVenta;
            }

            // ✅ MODIFICADO: Actualizar venta con campos de pago combinado + crédito
            $venta->update([
                'precio_venta' => $totalVenta,
                'valor_efectivo' => $valorEfectivo,
                'valor_transferencia' => $valorTransferencia,
                'valor_credito' => $valorCredito > 0 ? $valorCredito : null,
                'credito_pagado' => $valorCredito > 0 ? ($venta->credito_pagado ?? 0) : null,
                'fecha_credito' => $valorCredito > 0 ? ($venta->fecha_credito ?? date('Y-m-d')) : null,
                'fecha_promesa_pago' => $fechaPromesaPago,
                'valor_devuelto' => $valorDevuelto,
            ]);

            DB::commit();
            
            return new VentasResource($venta);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar venta: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (strpos($user->name, 'Asesor') === 0) {
                return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
            }

            DB::beginTransaction();
            
            $venta = Ventas::findOrFail($id);
            
            // Devolver stock a las variantes antes de eliminar
            $productosVenta = DB::table('venta_producto')
                ->where('id_venta', $venta->id)
                ->get();
                
            foreach ($productosVenta as $productoVenta) {
                // Devolver stock a la variante
                if ($productoVenta->id_producto_variante) {
                    $variante = ProductoVariante::find($productoVenta->id_producto_variante);
                    if ($variante) {
                        $variante->update([
                            'existente_en_almacen' => $variante->existente_en_almacen + intval($productoVenta->cantidad)
                        ]);
                    }
                }
                
                // Devolver stock al producto base
                $producto = Producto::find($productoVenta->id_producto);
                if ($producto) {
                    $producto->update([
                        'existente_en_almacen' => $producto->existente_en_almacen + intval($productoVenta->cantidad)
                    ]);
                }
            }

            $venta->delete();
            
            DB::commit();
            
            return response()->json(['message' => 'Venta eliminada correctamente']);
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al eliminar venta: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Error al eliminar la venta: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ NUEVOS MÉTODOS PARA MANEJO DE CRÉDITOS

    /**
     * Marcar un crédito como pagado
     */
    public function pagarCredito(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (strpos($user->name, 'Asesor') === 0) {
                return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
            }

            $venta = Ventas::findOrFail($id);
            
            // Validar que la venta tenga crédito pendiente
            if (is_null($venta->valor_credito) || $venta->valor_credito <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta venta no tiene crédito registrado'
                ], 400);
            }

            if ($venta->credito_pagado === 1) {
                return response()->json([
                    'success' => false,
                    'error' => 'El crédito de esta venta ya fue pagado'
                ], 400);
            }

            // Marcar como pagado
            $venta->update([
                'credito_pagado' => 1,
                'updated_at' => now()
            ]);

            Log::info("Crédito pagado - Venta ID: {$venta->id}, Valor: {$venta->valor_credito}");

            return response()->json([
                'success' => true,
                'message' => 'Crédito marcado como pagado correctamente',
                'data' => new VentasResource($venta)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al pagar crédito: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el pago del crédito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener lista de créditos pendientes
     */
    public function creditosPendientes(Request $request)
    {
        try {
            $ventas = Ventas::with(['vendedor'])
                ->whereNotNull('valor_credito')
                ->where('valor_credito', '>', 0)
                ->where('credito_pagado', 0) // Solo pendientes (no NULL ni 1)
                ->orderBy('fecha_promesa_pago', 'asc')
                ->get();

            $creditosConEstado = $ventas->map(function ($venta) {
                $diasVencimiento = \Carbon\Carbon::parse($venta->fecha_promesa_pago)->diffInDays(now(), false);
                
                return [
                    'venta' => new VentasResource($venta),
                    'estado_credito' => [
                        'dias_vencimiento' => $diasVencimiento,
                        'esta_vencido' => $diasVencimiento > 0,
                        'dias_restantes' => max(0, -$diasVencimiento),
                        'color_estado' => $diasVencimiento > 0 ? 'danger' : ($diasVencimiento > -3 ? 'warning' : 'success')
                    ]
                ];
            });

            return response()->json([
                'data' => $creditosConEstado,
                'resumen' => [
                    'total_creditos' => $ventas->count(),
                    'valor_total' => $ventas->sum('valor_credito'),
                    'vencidos' => $ventas->filter(function($v) { 
                        return \Carbon\Carbon::parse($v->fecha_promesa_pago)->isPast(); 
                    })->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener créditos pendientes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener la lista de créditos'
            ], 500);
        }
    }

    public function verDetalleCompleto($id)
    {
        try {
            $venta = Ventas::with(['vendedor'])->findOrFail($id);

            // Obtener productos con información de variantes usando el atributo del modelo
            $venta->productos_completos = $venta->productos_detallados;

            return response()->json([
                'success' => true,
                'data' => new VentasResource($venta)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener detalle completo de venta: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener la información de la venta: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener créditos por vendedor
     */
    public function creditosPorVendedor($vendedor)
    {
        try {
            $ventas = Ventas::with(['vendedor'])
                ->where('vendedor', $vendedor)
                ->whereNotNull('valor_credito')
                ->where('valor_credito', '>', 0)
                ->orderBy('fecha_promesa_pago', 'asc')
                ->get();

            $creditosResumen = [
                'vendedor_id' => $vendedor,
                'total_creditos' => $ventas->count(),
                'valor_total_creditos' => $ventas->sum('valor_credito'),
                'creditos_pendientes' => $ventas->where('credito_pagado', 0)->count(),
                'valor_pendiente' => $ventas->where('credito_pagado', 0)->sum('valor_credito'),
                'creditos_pagados' => $ventas->where('credito_pagado', 1)->count(),
                'valor_pagado' => $ventas->where('credito_pagado', 1)->sum('valor_credito'),
                'creditos_vencidos' => $ventas->filter(function($venta) {
                    return $venta->creditoVencido();
                })->count(),
            ];

            return response()->json([
                'success' => true,
                'resumen' => $creditosResumen,
                'ventas' => VentasResource::collection($ventas)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener créditos por vendedor: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener créditos del vendedor'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas generales de créditos
     */
    public function estadisticasCreditos()
    {
        try {
            $creditos = Ventas::whereNotNull('valor_credito')
                ->where('valor_credito', '>', 0)
                ->get();

            $estadisticas = [
                'resumen_general' => [
                    'total_creditos' => $creditos->count(),
                    'valor_total' => $creditos->sum('valor_credito'),
                    'creditos_pendientes' => $creditos->where('credito_pagado', 0)->count(),
                    'valor_pendiente' => $creditos->where('credito_pagado', 0)->sum('valor_credito'),
                    'creditos_pagados' => $creditos->where('credito_pagado', 1)->count(),
                    'valor_pagado' => $creditos->where('credito_pagado', 1)->sum('valor_credito'),
                    'porcentaje_pagados' => $creditos->count() > 0 ? 
                        round(($creditos->where('credito_pagado', 1)->count() / $creditos->count()) * 100, 2) : 0,
                ],
                'analisis_vencimientos' => [
                    'vencidos' => $creditos->filter(function($c) { return $c->creditoVencido(); })->count(),
                    'valor_vencido' => $creditos->filter(function($c) { return $c->creditoVencido(); })->sum('valor_credito'),
                    'proximos_a_vencer' => $creditos->filter(function($c) { 
                        return !$c->creditoVencido() && $c->dias_restantes <= 7; 
                    })->count(),
                    'vigentes' => $creditos->filter(function($c) { 
                        return !$c->creditoVencido() && $c->dias_restantes > 7; 
                    })->count(),
                ],
                'por_mes' => $this->obtenerCreditosPorMes($creditos),
                'por_vendedor' => $this->obtenerCreditosPorVendedor($creditos),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas de créditos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * Método auxiliar para obtener créditos por mes
     */
    private function obtenerCreditosPorMes($creditos)
    {
        return $creditos->groupBy(function($credito) {
            return $credito->fecha_credito ? $credito->fecha_credito->format('Y-m') : 'sin_fecha';
        })->map(function($creditosMes) {
            return [
                'cantidad' => $creditosMes->count(),
                'valor_total' => $creditosMes->sum('valor_credito'),
                'pendientes' => $creditosMes->where('credito_pagado', 0)->count(),
                'pagados' => $creditosMes->where('credito_pagado', 1)->count(),
            ];
        });
    }

    /**
     * Método auxiliar para obtener créditos por vendedor
     */
    private function obtenerCreditosPorVendedor($creditos)
    {
        return $creditos->groupBy('vendedor')->map(function($creditosVendedor, $vendedorId) {
            $vendedor = \App\Models\User::find($vendedorId);
            
            return [
                'vendedor_id' => $vendedorId,
                'vendedor_nombre' => $vendedor ? $vendedor->name : 'Vendedor ' . $vendedorId,
                'cantidad' => $creditosVendedor->count(),
                'valor_total' => $creditosVendedor->sum('valor_credito'),
                'pendientes' => $creditosVendedor->where('credito_pagado', 0)->count(),
                'valor_pendiente' => $creditosVendedor->where('credito_pagado', 0)->sum('valor_credito'),
                'pagados' => $creditosVendedor->where('credito_pagado', 1)->count(),
                'valor_pagado' => $creditosVendedor->where('credito_pagado', 1)->sum('valor_credito'),
                'vencidos' => $creditosVendedor->filter(function($c) { return $c->creditoVencido(); })->count(),
            ];
        });
    }

    /**
     * Extender fecha de promesa de pago
     */
    public function extenderCredito(Request $request, $id)
    {
        try {
            $user = Auth::user();
            if (strpos($user->name, 'Asesor') === 0) {
                return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
            }

            $data = $request->json()->all();
            $nuevaFecha = $data['nueva_fecha_promesa'] ?? null;
            $motivo = $data['motivo'] ?? 'Extensión solicitada';

            if (!$nuevaFecha) {
                return response()->json([
                    'success' => false,
                    'error' => 'Debe especificar la nueva fecha de promesa de pago'
                ], 400);
            }

            $venta = Ventas::findOrFail($id);
            
            // Validaciones
            if (is_null($venta->valor_credito) || $venta->valor_credito <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Esta venta no tiene crédito registrado'
                ], 400);
            }

            if ($venta->credito_pagado === 1) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se puede extender un crédito ya pagado'
                ], 400);
            }

            $fechaNueva = \Carbon\Carbon::parse($nuevaFecha);
            if ($fechaNueva->isPast()) {
                return response()->json([
                    'success' => false,
                    'error' => 'La nueva fecha debe ser futura'
                ], 400);
            }

            // Actualizar fecha
            $fechaAnterior = $venta->fecha_promesa_pago;
            $venta->update([
                'fecha_promesa_pago' => $nuevaFecha,
                'updated_at' => now()
            ]);

            Log::info("Crédito extendido - Venta ID: {$venta->id}, Fecha anterior: {$fechaAnterior}, Nueva fecha: {$nuevaFecha}, Motivo: {$motivo}");

            return response()->json([
                'success' => true,
                'message' => 'Fecha de promesa de pago actualizada correctamente',
                'data' => new VentasResource($venta)
            ]);

        } catch (\Exception $e) {
            Log::error('Error al extender crédito: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al extender el crédito: ' . $e->getMessage()
            ], 500);
        }
    }
}