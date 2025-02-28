<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngresoDeMercanciaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'codigo'=>$this->codigo,
            'fecha' => $this->fecha,
            'cantidad_de_ingreso' => $this->cantidad_de_ingreso,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
