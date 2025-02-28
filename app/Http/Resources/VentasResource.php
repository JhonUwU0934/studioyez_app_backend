<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VentasResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'cantidad' => $this->cantidad,
            'fecha' => $this->fecha,
            'precio_mayorista' => $this->precio_mayorista,
            'precio_unidad' => $this->precio_unidad,
            'precio_venta' => $this->precio_venta,
            'vendedor' => $this->vendedor,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
