<?php
// app/Http/Resources/ProductoImagenResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductoImagenResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'imagen' => $this->imagen,
            'alt_text' => $this->alt_text,
            'orden' => $this->orden,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
