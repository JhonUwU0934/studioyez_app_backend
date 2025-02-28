<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductoController extends Controller
{
    public function index()
    {
        $productos = Producto::latest('created_at')->get();
        return ProductoResource::collection($productos);
    }

    public function store(Request $request)
    {
        $request->merge([
            'existente_en_almacen' => str_replace(['.', ','], '', $request->input('existente_en_almacen')),
            'precio_por_mayor' => str_replace(['.', ','], '', $request->input('precio_por_mayor')),
            'precio_por_unidad' => str_replace(['.', ','], '', $request->input('precio_por_unidad'))
        ]);

        $data = $request->validate([
            'codigo' => 'required|string',
            'denominacion' => 'required|string',
            'imagen' => 'nullable|string',
            'existente_en_almacen' => 'nullable|string',
            'precio_por_mayor' => 'nullable|string',
            'precio_por_unidad' => 'nullable|string',
        ]);

        $producto = Producto::create($data);
        return new ProductoResource($producto);
    }

    public function show($id)
    {
        $producto = Producto::findOrFail($id);

        return new ProductoResource($producto);
    }

    public function update(Request $request, $id)
    {
        // Verificar si el usuario logeado tiene un nombre que comienza con "Asesor"
        $user = Auth::user();
        if (strpos($user->name, 'Asesor') === 0) {
            return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
        }

        $request->merge([
            'existente_en_almacen' => str_replace(['.', ','], '', $request->input('existente_en_almacen')),
            'precio_por_mayor' => str_replace(['.', ','], '', $request->input('precio_por_mayor')),
            'precio_por_unidad' => str_replace(['.', ','], '', $request->input('precio_por_unidad'))
        ]);

        $data = $request->validate([
            'codigo' => 'required|string',
            'denominacion' => 'required|string',
            'imagen' => 'nullable|string',
            'existente_en_almacen' => 'nullable|string',
            'precio_por_mayor' => 'nullable|string',
            'precio_por_unidad' => 'nullable|string',
        ]);
        $producto = Producto::findOrFail($id);

        $producto->update($data);
        return new ProductoResource($producto);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (strpos($user->name, 'Asesor') === 0) {
            return new JsonResponse(['message' => 'No tienes permiso para ejecutar esta acción.'], 403);
        }

        $producto = Producto::findOrFail($id);

        $producto->delete();
        return response()->json(['message' => 'Producto eliminado correctamente']);
    }
}
