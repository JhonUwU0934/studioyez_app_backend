<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TallaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'orden' => $this->orden,
            'activo' => $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
