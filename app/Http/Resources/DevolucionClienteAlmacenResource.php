<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolucionClienteAlmacenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Obtener variante solo si la relación existe
        $varianteSku = null;
        try {
            if ($this->relationLoaded('productoVariante') && $this->productoVariante) {
                $varianteSku = $this->productoVariante->sku;
            }
        } catch (\Exception $e) {
            $varianteSku = null;
        }

        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto_variante_id' => $this->producto_variante_id ?? null,
            'cantidad' => $this->cantidad,
            'codigo' => $this->codigo,
            'precio_venta' => $this->precio_venta,
            'cliente' => $this->cliente,
            'fecha' => $this->fecha,
            'quien_recibe' => $this->quien_recibe,
            'created_at' => $this->created_at,

            // Nombres legibles
            'producto_nombre' => $this->producto?->denominacion ?? 'Producto eliminado',
            'producto_codigo' => $this->producto?->codigo ?? '',
            'variante_sku' => $varianteSku,
            'quien_recibe_nombre' => $this->vendedor?->name ?? 'Usuario #' . $this->quien_recibe,
        ];
    }
}
