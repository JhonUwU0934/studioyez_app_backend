<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevolucionClienteAlmacenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto_variante_id' => $this->producto_variante_id,
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
            'variante_sku' => $this->productoVariante?->sku ?? null,
            'quien_recibe_nombre' => $this->vendedor?->name ?? 'Usuario #' . $this->quien_recibe,
        ];
    }
}
