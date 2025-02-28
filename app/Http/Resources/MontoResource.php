<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MontoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'creador_id' => $this->creador_id,
            'monto' => $this->monto,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
