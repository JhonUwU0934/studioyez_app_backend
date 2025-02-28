<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'denominacion' => $this->denominacion,
            'imagen' => $this->imagen,
            'existente_en_almacen' => $this->existente_en_almacen,
            'precio_por_mayor' => $this->precio_por_mayor,
            'precio_por_unidad' => $this->precio_por_unidad,
            'total' => $this->precio_por_unidad,
            'cantidad' => 1,
            'descuento' => 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
