<?php
// app/Http/Resources/ProductoResource.php (VERSIÓN MEJORADA CON CÁLCULOS)
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
            
            // INFORMACIÓN DE VARIANTES
            'tiene_variantes' => $this->relationLoaded('variantes') ? $this->variantes->count() > 0 : false,
            
            'galeria' => $this->when($this->relationLoaded('imagenes'), function() {
                return ProductoImagenResource::collection($this->imagenes);
            }, []),
            
            'variantes' => $this->when($this->relationLoaded('variantes'), function() {
                return ProductoVarianteResource::collection($this->variantes);
            }, []),
            
            'variantes_count' => $this->when($this->relationLoaded('variantes'), function() {
                return $this->variantes->count();
            }, 0),
            
            // NUEVOS CÁLCULOS DE STOCK
            'stock_info' => $this->when($this->relationLoaded('variantes'), function() {
                return $this->calcularStockInfo();
            }),
            
            'colores_disponibles' => $this->when($this->relationLoaded('variantes'), function() {
                $colores = $this->variantes
                    ->whereNotNull('color_id')
                    ->pluck('color')
                    ->filter()
                    ->unique('id');
                return ColorResource::collection($colores);
            }, []),
            
            'tallas_disponibles' => $this->when($this->relationLoaded('variantes'), function() {
                $tallas = $this->variantes
                    ->whereNotNull('talla_id')
                    ->pluck('talla')
                    ->filter()
                    ->unique('id')
                    ->sortBy('orden');
                return TallaResource::collection($tallas);
            }, []),
            
            // INFORMACIÓN DE STOCK POR COLOR Y TALLA
            'stock_por_color' => $this->when($this->relationLoaded('variantes'), function() {
                return $this->calcularStockPorColor();
            }, []),
            
            'stock_por_talla' => $this->when($this->relationLoaded('variantes'), function() {
                return $this->calcularStockPorTalla();
            }, []),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Calcular información general de stock
     */
    private function calcularStockInfo()
    {
        if (!$this->relationLoaded('variantes') || $this->variantes->isEmpty()) {
            return [
                'stock_total' => (int) $this->existente_en_almacen ?? 0,
                'tiene_stock' => ((int) $this->existente_en_almacen ?? 0) > 0,
                'variantes_con_stock' => 0,
                'variantes_sin_stock' => 0,
            ];
        }

        $stockTotal = $this->variantes->sum('existente_en_almacen');
        $variantesConStock = $this->variantes->where('existente_en_almacen', '>', 0)->count();
        $variantesSinStock = $this->variantes->where('existente_en_almacen', '<=', 0)->count();

        return [
            'stock_total' => (int) $stockTotal,
            'tiene_stock' => $stockTotal > 0,
            'variantes_con_stock' => $variantesConStock,
            'variantes_sin_stock' => $variantesSinStock,
            'stock_producto_base' => (int) $this->existente_en_almacen ?? 0,
        ];
    }

    /**
     * Calcular stock agrupado por color
     */
    private function calcularStockPorColor()
    {
        if (!$this->relationLoaded('variantes') || $this->variantes->isEmpty()) {
            return [];
        }

        $stockPorColor = [];
        
        foreach ($this->variantes->whereNotNull('color_id') as $variante) {
            $colorId = $variante->color_id;
            $colorNombre = $variante->color ? $variante->color->nombre : 'Color ' . $colorId;
            
            if (!isset($stockPorColor[$colorId])) {
                $stockPorColor[$colorId] = [
                    'color_id' => $colorId,
                    'color_nombre' => $colorNombre,
                    'stock_total' => 0,
                    'variantes_count' => 0,
                    'tiene_stock' => false,
                ];
            }
            
            $stockPorColor[$colorId]['stock_total'] += (int) $variante->existente_en_almacen;
            $stockPorColor[$colorId]['variantes_count']++;
            $stockPorColor[$colorId]['tiene_stock'] = $stockPorColor[$colorId]['stock_total'] > 0;
        }

        return array_values($stockPorColor);
    }

    /**
     * Calcular stock agrupado por talla
     */
    private function calcularStockPorTalla()
    {
        if (!$this->relationLoaded('variantes') || $this->variantes->isEmpty()) {
            return [];
        }

        $stockPorTalla = [];
        
        foreach ($this->variantes->whereNotNull('talla_id') as $variante) {
            $tallaId = $variante->talla_id;
            $tallaNombre = $variante->talla ? $variante->talla->nombre : 'Talla ' . $tallaId;
            $tallaOrden = $variante->talla ? $variante->talla->orden : 999;
            
            if (!isset($stockPorTalla[$tallaId])) {
                $stockPorTalla[$tallaId] = [
                    'talla_id' => $tallaId,
                    'talla_nombre' => $tallaNombre,
                    'talla_orden' => $tallaOrden,
                    'stock_total' => 0,
                    'variantes_count' => 0,
                    'tiene_stock' => false,
                ];
            }
            
            $stockPorTalla[$tallaId]['stock_total'] += (int) $variante->existente_en_almacen;
            $stockPorTalla[$tallaId]['variantes_count']++;
            $stockPorTalla[$tallaId]['tiene_stock'] = $stockPorTalla[$tallaId]['stock_total'] > 0;
        }

        // Ordenar por talla_orden
        uasort($stockPorTalla, function($a, $b) {
            return $a['talla_orden'] <=> $b['talla_orden'];
        });

        return array_values($stockPorTalla);
    }
}