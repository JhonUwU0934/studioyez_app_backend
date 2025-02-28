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
        $devolucionClienteAlmacen = DevolucionClienteAlmacen::latest('created_at')->get();
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
            'cantidad' => 'required|string',
        ]);

        $codigoProducto = $data['codigo_producto'];
        $codigoFactura = $data['codigo_factura'];
        $cantidad = $data['cantidad'];

        // Obtener el producto por su código
        $producto = Producto::where('codigo', $codigoProducto)->first();

        // Obtener la venta por su código de factura
        $venta = Ventas::where('codigo_factura', $codigoFactura)->first();

        // Calcular la cantidad total en el almacén después de la devolución
        $cantidadTotal = $producto->existente_en_almacen + $cantidad;

        // Actualizar la cantidad del producto en el stock
        $producto->update([
            'existente_en_almacen' => $cantidadTotal
        ]);

        // Obtener los detalles de la venta para el producto devuelto
        $ventaProducto = \DB::connection('mysql')
            ->table('venta_producto')
            ->where('id_venta', $venta->id)
            ->first();

        // Insertar registro de devolución en el almacén
        DevolucionClienteAlmacen::insert([
            'producto_id' => $producto->id,
            'codigo' => $codigoProducto, // Agregar el código del producto
            'cantidad' => $cantidad,
            'precio_venta' => $ventaProducto->total_producto,
            'cliente' => $venta->nombre_comprador,
            'fecha' => date('Y-m-d'),
            'quien_recibe' => Auth::id(),
            'created_at' => Carbon::now()
        ]);

        // Devolver los detalles de la venta del producto
        return $ventaProducto;
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
