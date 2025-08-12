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

            $productosData = $data['productos'];
            $totalVenta = 0;
            $error = null;

            // PRIMERA PASADA: Validar existencias antes de procesar
            foreach ($productosData as $productoData) {
                $cantidadProducto = intval($productoData['cantidad']);
                $codigoProducto = $productoData['codigo'];
                $totalProducto = floatval($productoData['total']);
                $descuentoProducto = floatval($productoData['descuento'] ?? 0);
                
                // NUEVO: Datos de variante (obligatorios)
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

                // NUEVO: Buscar variante específica
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

            DB::beginTransaction();
            
            // Generar el código de venta aleatorio
            $codigoVenta = 'STDY' . date('Y') . rand(1000, 9999);

            // Crear la venta
            $venta = Ventas::create([
                'nombre_comprador' => $nombreComprador,
                'numero_comprador' => $numeroComprador,
                'vendedor' => $vendedor,
                'codigo_factura' => $codigoVenta,
                'precio_venta' => $totalVenta,
                'fecha' => date('Y-m-d'),
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

            // MODIFICADO: Obtener productos con información de variantes
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

            // Actualizar total de venta
            $venta->update(['precio_venta' => $totalVenta]);

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
}