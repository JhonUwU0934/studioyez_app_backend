<?php
namespace App\Http\Controllers\Api;

use App\Models\Talla;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TallaResource;

class TallaController extends Controller
{
    public function index()
    {
        $tallas = Talla::where('activo', true)->orderBy('orden')->get();
        return TallaResource::collection($tallas);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:50|unique:tallas',
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $talla = Talla::create($data);
        return new TallaResource($talla);
    }

    public function show($id)
    {
        $talla = Talla::findOrFail($id);
        return new TallaResource($talla);
    }

    public function update(Request $request, $id)
    {
        $talla = Talla::findOrFail($id);
        
        $data = $request->validate([
            'nombre' => 'required|string|max:50|unique:tallas,nombre,' . $talla->id,
            'orden' => 'nullable|integer',
            'activo' => 'boolean',
        ]);

        $talla->update($data);
        return new TallaResource($talla);
    }

    public function destroy($id)
    {
        $talla = Talla::findOrFail($id);
        $talla->delete();
        
        return response()->json(['message' => 'Talla eliminada correctamente']);
    }
}