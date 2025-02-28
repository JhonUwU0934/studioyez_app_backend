<?php

namespace App\Http\Controllers\Api;

use App\Models\IngresoDeMercancia;
use Illuminate\Http\Request;
use App\Http\Resources\IngresoDeMercanciaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\Producto;
use App\Http\Controllers\Controller;


class IngresoDeMercanciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ingreso = IngresoDeMercancia::latest('created_at')->get();
        return IngresoDeMercanciaResource::collection($ingreso);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->merge([
            'producto_id' => str_replace(['.', ','], '', $request->input('producto_id')),
            'fecha' => str_replace(['.', ','], '', $request->input('fecha')),
            'cantidad_de_ingreso' => str_replace(['.', ','], '', $request->input('cantidad_de_ingreso'))
        ]);

        $data = $request->validate([
            'producto_id' => 'required|string',
            'fecha' => 'required|string',
            'cantidad_de_ingreso' => 'nullable|string',
        ]);

        $producto = Producto::findOrFail($data['producto_id']);

        $data['codigo'] = $producto->codigo;

        $ingreso = IngresoDeMercancia::create($data);

        $producto->existente_en_almacen += (int)$data['cantidad_de_ingreso'];

        $producto->save();

        return new IngresoDeMercanciaResource($ingreso);
    }


    /**
     * Display the specified resource.
     */
    public function show(IngresoDeMercancia $ingresoDeMercancia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IngresoDeMercancia $ingresoDeMercancia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IngresoDeMercancia $ingresoDeMercancia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IngresoDeMercancia $ingresoDeMercancia)
    {
        //
    }
}
