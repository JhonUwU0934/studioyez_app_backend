<?php
// app/Http/Controllers/Api/ProductoImagenController.php
namespace App\Http\Controllers\Api;

use App\Models\ProductoImagen;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoImagenResource;

class ProductoImagenController extends Controller
{
    public function index($productoId)
    {
        $imagenes = ProductoImagen::where('producto_id', $productoId)
            ->orderBy('orden')
            ->get();
        
        return ProductoImagenResource::collection($imagenes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'imagen' => 'required|string',
            'alt_text' => 'nullable|string',
            'orden' => 'nullable|integer',
        ]);

        $imagen = ProductoImagen::create($data);
        return new ProductoImagenResource($imagen);
    }

    public function show($id)
    {
        $imagen = ProductoImagen::findOrFail($id);
        return new ProductoImagenResource($imagen);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'imagen' => 'required|string',
            'alt_text' => 'nullable|string',
            'orden' => 'nullable|integer',
        ]);

        $imagen = ProductoImagen::findOrFail($id);
        $imagen->update($data);
        
        return new ProductoImagenResource($imagen);
    }

    public function destroy($id)
    {
        $imagen = ProductoImagen::findOrFail($id);
        $imagen->delete();
        
        return response()->json(['message' => 'Imagen eliminada correctamente']);
    }
}