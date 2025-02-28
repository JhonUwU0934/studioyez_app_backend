<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GastosResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'monto' => $this->monto,
            'fecha' => $this->fecha,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
