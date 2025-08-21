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
        // Obtener el producto relacionado (directo o a través de variante)
        $productoBase = $this->producto_relacionado;
        
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto_variante_id' => $this->producto_variante_id,
            'fecha' => $this->fecha,
            'cantidad_de_ingreso' => $this->cantidad_de_ingreso,
            'codigo' => $this->codigo,
            
            // Información sobre el tipo de ingreso
            'es_ingreso_de_variante' => $this->es_ingreso_de_variante,
            
            // Información del producto base (directo o a través de variante)
            'producto_base' => $productoBase ? [
                'id' => $productoBase->id,
                'codigo' => $productoBase->codigo,
                'denominacion' => $productoBase->denominacion,
                'existente_en_almacen' => $productoBase->existente_en_almacen,
                'precio_por_mayor' => $productoBase->precio_por_mayor,
                'precio_por_unidad' => $productoBase->precio_por_unidad,
            ] : null,
            
            // Información de la variante específica si existe
            'producto_variante' => $this->whenLoaded('productoVariante', function () {
                return [
                    'id' => $this->productoVariante->id,
                    'sku' => $this->productoVariante->sku,
                    'existente_en_almacen' => $this->productoVariante->existente_en_almacen,
                    'precio_por_mayor' => $this->productoVariante->precio_por_mayor,
                    'precio_por_unidad' => $this->productoVariante->precio_por_unidad,
                    'imagen_variante' => $this->productoVariante->imagen_variante,
                    'activo' => $this->productoVariante->activo,
                    
                    // Información del color si existe
                    'color' => $this->productoVariante->color ? [
                        'id' => $this->productoVariante->color->id,
                        'nombre' => $this->productoVariante->color->nombre,
                        'codigo_hex' => $this->productoVariante->color->codigo_hex,
                    ] : null,
                    
                    // Información de la talla si existe
                    'talla' => $this->productoVariante->talla ? [
                        'id' => $this->productoVariante->talla->id,
                        'nombre' => $this->productoVariante->talla->nombre,
                        'orden' => $this->productoVariante->talla->orden ?? 0,
                    ] : null,
                ];
            }),
            
            // Campos calculados para facilitar el uso en frontend
            'nombre_completo' => $this->nombre_completo,
            'sku_mostrar' => $this->sku_mostrar,
            'stock_actual' => $this->stock_actual,
            
            // Información adicional útil
            'resumen_ingreso' => [
                'tipo' => $this->es_ingreso_de_variante ? 'variante' : 'producto_base',
                'descripcion' => $this->es_ingreso_de_variante ? 'Ingreso a variante específica' : 'Ingreso a producto base',
                'item_afectado' => $this->nombre_completo,
                'codigo_sku' => $this->sku_mostrar,
                'cantidad_ingresada' => $this->cantidad_de_ingreso,
                'fecha_ingreso' => $this->fecha->format('d/m/Y'),
            ],
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
    
    /**
     * Obtener información adicional con relaciones cargadas
     */
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '2.0',
                'supports_variants' => true,
                'fields_explanation' => [
                    'producto_id' => 'ID del producto base (NULL si es ingreso a variante específica)',
                    'producto_variante_id' => 'ID de la variante específica (NULL si es ingreso a producto base)',
                    'es_ingreso_de_variante' => 'true = ingreso a variante, false = ingreso a producto base',
                    'nombre_completo' => 'Nombre del producto con detalles de variante si aplica',
                    'sku_mostrar' => 'SKU de la variante o código del producto base',
                ]
            ]
        ];
    }
}