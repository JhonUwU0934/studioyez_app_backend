<?php
namespace App\Http\Controllers\Api;

use App\Models\ProductoVariante;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoVarianteResource;

class ProductoVarianteController extends Controller
{
    public function index($productoId = null)
    {
        $query = ProductoVariante::with(['producto', 'color', 'talla']);
        
        if ($productoId) {
            $query->where('producto_id', $productoId);
        }
        
        $variantes = $query->get();
        return ProductoVarianteResource::collection($variantes);
    }

    public function store(Request $request)
    {
        $request->merge([
            'existente_en_almacen' => str_replace(['.', ','], '', $request->input('existente_en_almacen')),
            'precio_por_mayor' => str_replace(['.', ','], '', $request->input('precio_por_mayor')),
            'precio_por_unidad' => str_replace(['.', ','], '', $request->input('precio_por_unidad'))
        ]);

        $data = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'color_id' => 'nullable|exists:colores,id',
            'talla_id' => 'nullable|exists:tallas,id',
            'sku' => 'nullable|string|unique:producto_variantes',
            'existente_en_almacen' => 'nullable|integer',
            'precio_por_mayor' => 'nullable|string',
            'precio_por_unidad' => 'nullable|string',
            'imagen_variante' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $variante = ProductoVariante::create($data);
        return new ProductoVarianteResource($variante);
    }

    public function show($id)
    {
        $variante = ProductoVariante::with(['producto', 'color', 'talla'])->findOrFail($id);
        return new ProductoVarianteResource($variante);
    }

    public function update(Request $request, $id)
    {
        $variante = ProductoVariante::findOrFail($id);
        
        $request->merge([
            'existente_en_almacen' => str_replace(['.', ','], '', $request->input('existente_en_almacen')),
            'precio_por_mayor' => str_replace(['.', ','], '', $request->input('precio_por_mayor')),
            'precio_por_unidad' => str_replace(['.', ','], '', $request->input('precio_por_unidad'))
        ]);

        $data = $request->validate([
            'color_id' => 'nullable|exists:colores,id',
            'talla_id' => 'nullable|exists:tallas,id',
            'sku' => 'nullable|string|unique:producto_variantes,sku,' . $variante->id,
            'existente_en_almacen' => 'nullable|integer',
            'precio_por_mayor' => 'nullable|string',
            'precio_por_unidad' => 'nullable|string',
            'imagen_variante' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $variante->update($data);
        return new ProductoVarianteResource($variante);
    }

    public function destroy($id)
    {
        $variante = ProductoVariante::findOrFail($id);
        $variante->delete();
        
        return response()->json(['message' => 'Variante eliminada correctamente']);
    }
}