<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Monto;
use App\Http\Resources\MontoResource;
use App\Models\Ventas;
use App\Models\Gastos;
class MontoController extends Controller
{
    public function index()
    {
        $montos = Monto::latest('created_at')->get();
        return MontoResource::collection($montos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'creador_id' => 'required|exists:users,id',
            'monto' => 'required|numeric',
        ]);
        $monto = Monto::create($data);

        // Obtener la misma información que se muestra en el login
        $cantidadVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('cantidad');
        $totalVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('precio_venta');
        $totalGastos = Gastos::whereDate('fecha', now()->toDateString())->sum('monto');
        $montoDiario = Monto::whereDate('created_at', now()->toDateString())->first()->monto;
        $montoID = Monto::whereDate('created_at', now()->toDateString())->first()->id;

        $montoDiario = $monto ? $monto->monto : 0; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0
        $montoID = $monto ? $monto->id : null; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0

        $balanceDiario = ($totalVentas + $montoDiario) - $totalGastos;

        return response()->json([
            'success' => true,
            'action' => 'Creación de monto',
            'message' => 'Monto creado exitosamente',
            'code' => 200,
            'monto_id' => $montoID,
            'monto_actualizado' => new MontoResource($monto),
            'cantidad_ventas' => $cantidadVentas,
            'total_ventas' => $totalVentas,
            'total_gastos' => $totalGastos,
            'balance_diario' => $balanceDiario,
            'monto_diario' => $montoDiario,
            'error' => null
        ], 200);

    }

    public function show($id)
    {
        $monto = Monto::findOrFail($id);

        return new MontoResource($monto);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'monto' => 'required|numeric',
        ]);

        $monto = Monto::findOrFail($id);

        $monto->update($data);

        // Obtener la misma información que se muestra en el login
        $cantidadVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('cantidad');
        $totalVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('precio_venta');
        $totalGastos = Gastos::whereDate('fecha', now()->toDateString())->sum('monto');
        $montoDiario = Monto::whereDate('created_at', now()->toDateString())->first()->monto;
        $montoID = Monto::whereDate('created_at', now()->toDateString())->first()->id;

        $montoDiario = $monto ? $monto->monto : 0; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0
        $montoID = $monto ? $monto->id : null; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0

        $balanceDiario = ($totalVentas + $montoDiario) - $totalGastos;

        return response()->json([
            'success' => true,
            'action' => 'Actualización de monto',
            'message' => 'Monto actualizado exitosamente',
            'code' => 200,
            'monto_id' => $montoID,
            'monto_actualizado' => new MontoResource($monto),
            'cantidad_ventas' => $cantidadVentas,
            'total_ventas' => $totalVentas,
            'total_gastos' => $totalGastos,
            'balance_diario' => $balanceDiario,
            'monto_diario' => $montoDiario,
            'error' => null
        ], 200);
    }

    public function destroy(Monto $monto)
    {
        $monto->delete();
        return response()->json(['message' => 'Monto eliminado correctamente']);
    }
}
