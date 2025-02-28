<?php

namespace App\Http\Controllers\Api;

use App\Models\Gastos;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\GastosResource;

class GastoController extends Controller
{
    public function index()
    {
        $gastos = Gastos::latest('created_at')->get();
        return GastosResource::collection($gastos);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descripcion' => 'required|string',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
        ]);

        $gasto = Gastos::create($data);
        return new GastosResource($gasto);
    }

    public function show($id)
    {
        $gasto = Gastos::findOrFail($id);

        return new GastosResource($gasto);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'descripcion' => 'required|string',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
        ]);

        $gasto = Gastos::findOrFail($id);

        $gasto->update($data);

        return new GastosResource($gasto);
    }

    public function destroy($id)
    {
        $gasto = Gastos::findOrFail($id);

        $gasto->delete();
        return response()->json(['message' => 'Gasto eliminado correctamente']);
    }
}
