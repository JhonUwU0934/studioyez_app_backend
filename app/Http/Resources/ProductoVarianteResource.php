<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductoVarianteResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'color_id' => $this->color_id,
            'talla_id' => $this->talla_id,
            'sku' => $this->sku,
            'existente_en_almacen' => $this->existente_en_almacen,
            'precio_por_mayor' => $this->precio_por_mayor,
            'precio_por_unidad' => $this->precio_por_unidad,
            'imagen_variante' => $this->imagen_variante,
            'activo' => $this->activo,
            'producto' => $this->whenLoaded('producto', function () {
                return [
                    'id' => $this->producto->id,
                    'codigo' => $this->producto->codigo,
                    'denominacion' => $this->producto->denominacion,
                ];
            }),
            'color' => $this->whenLoaded('color', function () {
                return new ColorResource($this->color);
            }),
            'talla' => $this->whenLoaded('talla', function () {
                return new TallaResource($this->talla);
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}