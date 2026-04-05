<?php

namespace App\Http\Controllers\Api;

use App\Models\DevolucionClienteAlmacen;
use Illuminate\Http\Request;
use App\Http\Resources\DevolucionClienteAlmacenResource;
use App\Models\Producto;
use App\Http\Controllers\Controller; // Asegúrate de importar la clase Controller correctamente
use App\Models\Ventas;
use App\Models\VentaProducto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DevolucionClienteAlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $devolucionClienteAlmacen = DevolucionClienteAlmacen::with(['producto', 'productoVariante', 'vendedor'])
            ->latest('created_at')->get();
        return DevolucionClienteAlmacenResource::collection($devolucionClienteAlmacen);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo_factura' => 'required|string',
            'codigo_producto' => 'required|string',
            'producto_variante_id' => 'nullable|integer',
            'cantidad' => 'required|integer|min:1',
        ]);

        $codigoProducto = $data['codigo_producto'];
        $codigoFactura = $data['codigo_factura'];
        $cantidad = (int) $data['cantidad'];
        $varianteId = $data['producto_variante_id'] ?? null;

        $producto = Producto::where('codigo', $codigoProducto)->first();
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $venta = Ventas::where('codigo_factura', $codigoFactura)->first();
        if (!$venta) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        \DB::beginTransaction();
        try {
            // Revertir stock al producto base
            $producto->existente_en_almacen = (int) $producto->existente_en_almacen + $cantidad;
            $producto->save();

            // Revertir stock a la variante si aplica
            if ($varianteId) {
                $variante = ProductoVariante::find($varianteId);
                if ($variante) {
                    $variante->existente_en_almacen = (int) $variante->existente_en_almacen + $cantidad;
                    $variante->save();
                }
            }

            // Obtener precio de la venta
            $ventaProducto = \DB::table('venta_producto')
                ->where('id_venta', $venta->id)
                ->where('id_producto', $producto->id)
                ->first();

            $precioVenta = $ventaProducto ? $ventaProducto->total_producto : 0;

            // Crear registro de devolución
            $devolucion = DevolucionClienteAlmacen::create([
                'producto_id' => $producto->id,
                'producto_variante_id' => $varianteId,
                'codigo' => $codigoProducto,
                'cantidad' => $cantidad,
                'precio_venta' => $precioVenta,
                'cliente' => $venta->nombre_comprador,
                'fecha' => date('Y-m-d'),
                'quien_recibe' => Auth::id(),
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Devolución registrada exitosamente',
                'data' => $devolucion
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            return response()->json(['error' => 'Error al registrar devolución', 'message' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(DevolucionClienteAlmacen $devolucionClienteAlmacen)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DevolucionClienteAlmacen $devolucionClienteAlmacen)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DevolucionClienteAlmacen $devolucionClienteAlmacen)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DevolucionClienteAlmacen $devolucionClienteAlmacen)
    {
        //
    }
}
