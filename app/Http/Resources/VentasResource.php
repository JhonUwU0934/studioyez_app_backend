<?php
// app/Http/Resources/VentasResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class VentasResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'codigo_factura' => $this->codigo_factura,
            'nombre_comprador' => $this->nombre_comprador,
            'numero_comprador' => $this->numero_comprador,
            'vendedor' => $this->vendedor,
            'precio_venta' => (float) $this->precio_venta,
            'fecha' => $this->fecha,
            'estado_pago' => $this->estado_pago,
            'url_factura' => $this->url_factura,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Información de productos con variantes
            'productos' => $this->getProductosConVariantes(),
            
            // Totales calculados usando los nuevos métodos del modelo
            'totales' => [
                'cantidad_productos_diferentes' => $this->cantidad_productos_diferentes,
                'total_productos_vendidos' => $this->total_productos,
                'total_descuentos' => (float) $this->total_descuentos,
                'subtotal' => (float) ($this->precio_venta + $this->total_descuentos),
            ],
            
            // Información de variantes vendidas
            'variantes_info' => [
                'tiene_variantes' => $this->tieneVariantes(),
                'colores_vendidos' => $this->when($this->tieneVariantes(), function() {
                    return $this->getColoresVendidos()->map(function($color) {
                        return [
                            'id' => $color->id,
                            'nombre' => $color->nombre,
                            'codigo_hex' => $color->codigo_hex ?? null,
                        ];
                    });
                }),
                'tallas_vendidas' => $this->when($this->tieneVariantes(), function() {
                    return $this->getTallasVendidas()->map(function($talla) {
                        return [
                            'id' => $talla->id,
                            'nombre' => $talla->nombre,
                            'orden' => $talla->orden,
                        ];
                    });
                }),
            ],
            
            // Relaciones opcionales
            'vendedor_info' => $this->when($this->relationLoaded('vendedor'), function () {
                return $this->vendedor;
            }),
        ];
    }

    private function getProductosConVariantes()
    {
        // Usar el nuevo atributo del modelo que ya incluye toda la lógica
        return $this->productos_detallados;
    }
    
    // Método alternativo usando las relaciones del modelo
    private function getProductosConVariantesRelaciones()
    {
        // Cargar relaciones si no están ya cargadas
        if (!$this->relationLoaded('ventaProductos')) {
            $this->load([
                'ventaProductos.producto',
                'ventaProductos.variante.color',
                'ventaProductos.variante.talla'
            ]);
        }
        
        return $this->ventaProductos->map(function ($ventaProducto) {
            return $ventaProducto->getInformacionCompleta();
        });
    }
}