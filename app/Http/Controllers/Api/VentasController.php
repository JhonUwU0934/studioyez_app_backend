<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use App\Models\VentaProducto;
use App\Models\Ventas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\VentasResource;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;

class VentasController extends Controller
{
    public function index()
    {
        $ventas =Ventas::with(['vendedor'])->latest('created_at')->get();

        // Obtener los productos asociados a cada venta y anidarlos dentro del objeto de venta
        $ventasConProductos = [];

        foreach ($ventas as $venta) {
            $productos = DB::table('productos')
                ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
                ->where('venta_producto.id_venta', $venta->id)
                ->select('productos.*', 'venta_producto.cantidad', 'venta_producto.total_producto')
                ->get();

            $venta->productos = $productos;
            $ventasConProductos[] = $venta;
        }

        return ['data' => $ventasConProductos];
    }

    public function store(Request $request)
    {
        $data = $request->json()->all();

        $nombreComprador = $data['nombre_comprador'];
        $numeroComprador = $data['numero_comprador'];
        $vendedor = $data['vendedor'];

        // Validar otros campos según tus necesidades
        // ...

        $productosData = $data['productos'];
        $totalVenta = 0;
        $error = null;

        foreach ($productosData as $productoData) {
            $cantidadProducto = $productoData['cantidad'];
            $codigoProducto = $productoData['codigo'];
            $totalProducto = $productoData['total'];
            $descuentoProducto = $productoData['descuento'];
            $producto = Producto::where('codigo', $codigoProducto)->first();

            if ($producto) {
                // Verificar si la existencia del producto es suficiente para la venta
                if ($producto->existente_en_almacen >= $cantidadProducto) {
                    $totalVenta += (float) $totalProducto;

                    // Asociar el producto a la venta (sin guardar el registro)
                    $ventaProducto = new VentaProducto([
                        'id_producto' => $producto->id,
                        'cantidad' => $cantidadProducto,
                        'total' => $totalProducto,
                        'descuento' => $descuentoProducto
                    ]);

                    // No necesitamos guardar aquí ya que el registro se guarda cuando se guarda la venta
                } else {
                    // Actualizar el mensaje de error si la existencia no es suficiente
                    $error = "No hay suficiente existencia para el producto '{$producto->denominacion}' (Código: {$producto->codigo})";
                    break; // Detener el proceso de venta
                }
            }
        }

        if ($error) {
            // Devolver mensaje de error si la existencia no es suficiente
            return response()->json(
                ['success'=>false,'error' => $error], 400);
        }

        // Generar el código de venta aleatorio (por ejemplo: STDY2001)
        $codigoVenta = 'STDY' . date('Y') . rand(1000, 9999);

        // Agregar el código de venta a los datos antes de crear la venta
        $data['codigo_factura'] = $codigoVenta;

        // Agregar el total de la venta a los datos antes de crear la venta
        $data['precio_venta'] = $totalVenta;
        $data['fecha'] = date('Y-m-d');

        // Crear la venta con los datos actualizados
        $venta = Ventas::create($data);

        // Asociar los productos a la venta y guardar los registros en la tabla pivot
        foreach ($productosData as $productoData) {
            $codigoProducto = $productoData['codigo'];
            $cantidadProducto = $productoData['cantidad'];
            $totalProducto = $productoData['total'];
            $descuentoProducto = $productoData['descuento'];

            $producto = Producto::where('codigo', $codigoProducto)->first();

            if ($producto) {

                \DB::connection('mysql')
                    ->table('venta_producto')
                    ->insertGetId([
                        'id_venta' => $venta->id,
                        'id_producto' => $producto->id,
                        'cantidad' => $cantidadProducto,
                        'total_producto' => $totalProducto,
                        'descuento' => $descuentoProducto
                    ]);

                // Actualizar la existencia del producto en el almacén
                $producto->update([
                    'existente_en_almacen' => $producto->existente_en_almacen - $cantidadProducto
                ]);
            }
        }

        return new VentasResource($venta);
    }

    public function show($id)
    {
        $venta = Ventas::with(['vendedor'])->findOrFail($id);

        // Obtener los productos asociados a la venta
        $productos = DB::table('productos')
            ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
            ->where('venta_producto.id_venta', $venta->id)
            ->select('productos.*', 'venta_producto.cantidad', 'venta_producto.total_producto')
            ->get();

        $venta->productos = $productos;

        return ['data' => $venta];
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (strpos($user->name, 'Asesor') === 0) {
            return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
        }

        $data = $request->json()->all();

        // Buscar la venta existente por su ID
        $venta = Ventas::findOrFail($id);

        // Validar otros campos según tus necesidades
        // ...

        $productosData = $data['productos'];
        $totalVenta = 0;

        foreach ($productosData as $productoData) {
            $cantidadProducto = $productoData['cantidad'];
            $codigoProducto = $productoData['codigo'];
            $totalProducto = $productoData['total'];
            $descuentoProducto = $productoData['descuento'];

            $producto = Producto::where('codigo', $codigoProducto)->first();

            if ($producto) {
                $totalVenta += (float) $totalProducto;

                // Verificar si el producto ya está asociado con la venta
                $ventaProductoExistente = \DB::connection('mysql')
                    ->table('venta_producto')
                    ->where('id_venta', $venta->id)
                    ->where('id_producto', $producto->id)
                    ->first();

                if (!$ventaProductoExistente) {
                    // Insertar el nuevo producto en la tabla venta_producto
                    \DB::connection('mysql')
                        ->table('venta_producto')
                        ->insert([
                            'id_venta' => $venta->id,
                            'id_producto' => $producto->id,
                            'cantidad' => $cantidadProducto,
                            'total_producto' => $totalProducto,
                            'descuento' => $descuentoProducto
                        ]);

                    // Actualizar la cantidad del producto solo si la venta está finalizada
                    // Puedes agregar esta lógica aquí si es necesario
                }
            }
        }

        // Actualizar el total de la venta con los datos actualizados
        \DB::connection('mysql')
            ->table('ventas')
            ->where('id', $venta->id)
            ->update(['precio_venta' => $totalVenta]);

        return new VentasResource($venta);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (strpos($user->name, 'Asesor') === 0) {
            return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
        }

        $ventas = Ventas::findOrFail($id);

        $ventas->delete();
        return response()->json(['message' => 'Venta eliminada correctamente']);
    }
}
