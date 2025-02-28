<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Balance;

class BalanceController extends Controller
{
    public function store(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'ventas_diarias' => 'required|numeric',
            'gastos_diarios' => 'required|numeric',
            'cantidad_ventas' => 'required|integer',
            'total' => 'required|numeric',
        ]);

        // Crear una nueva instancia del modelo Balance y asignar los valores
        $balance = new Balance([
            'ventas_diarias' => $request->input('ventas_diarias'),
            'gastos_diarios' => $request->input('gastos_diarios'),
            'cantidad_ventas' => $request->input('cantidad_ventas'),
            'total' => $request->input('total'),
        ]);

        // Guardar el balance en la base de datos
        $balance->save();

        // Retornar una respuesta exitosa
        return response()->json([
            'message' => 'Balance diario insertado correctamente.',
            'data' => $balance,
        ], 201);
    }
}
