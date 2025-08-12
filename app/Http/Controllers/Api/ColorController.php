<?php
namespace App\Http\Controllers\Api;

use App\Models\Color;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ColorResource;

class ColorController extends Controller
{
    public function index()
    {
        $colores = Color::where('activo', true)->orderBy('nombre')->get();
        return ColorResource::collection($colores);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:colores',
            'codigo_hex' => 'nullable|string|max:7',
            'activo' => 'boolean',
        ]);

        $color = Color::create($data);
        return new ColorResource($color);
    }

    public function show($id)
    {
        $color = Color::findOrFail($id);
        return new ColorResource($color);
    }

    public function update(Request $request, $id)
    {
        $color = Color::findOrFail($id);
        
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:colores,nombre,' . $color->id,
            'codigo_hex' => 'nullable|string|max:7',
            'activo' => 'boolean',
        ]);

        $color->update($data);
        return new ColorResource($color);
    }

    public function destroy($id)
    {
        $color = Color::findOrFail($id);
        $color->delete();
        
        return response()->json(['message' => 'Color eliminado correctamente']);
    }
}