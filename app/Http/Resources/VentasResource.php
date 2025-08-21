<?php
// app/Http/Resources/VentasResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class VentasResource extends JsonResource
{
    public function toArray($request)
    {
        try {
            Log::info('VentasResource: Iniciando para venta ID: ' . $this->id);
            
            return [
                'id' => $this->id,
                'codigo_factura' => $this->codigo_factura,
                'nombre_comprador' => $this->nombre_comprador,
                'numero_comprador' => $this->numero_comprador,
                'vendedor' => $this->vendedor,
                'precio_venta' => (float) $this->precio_venta,
                'fecha' => $this->fecha,
                'estado_pago' => $this->estado_pago ?? 'pendiente',
                'url_factura' => $this->url_factura,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                
                // ✅ INFORMACIÓN DE PAGO COMBINADO + CRÉDITO
                'pago_info' => $this->getPagoInfoSeguro(),

                // ✅ INFORMACIÓN ESPECÍFICA DE CRÉDITO
                'credito_info' => $this->getCreditoInfoSeguro(),
                
                // ✅ INFORMACIÓN DE PRODUCTOS CON VARIANTES (usando tu método del modelo)
                'productos' => $this->getProductosDelModelo(),
                
                // ✅ TOTALES CALCULADOS
                'totales' => $this->getTotalesSeguro(),
                
                // ✅ INFORMACIÓN DE VARIANTES VENDIDAS
                'variantes_info' => $this->getVariantesInfoDelModelo(),
                
                // ✅ ESTADÍSTICAS DETALLADAS DE PAGO
                'estadisticas_pago' => $this->getEstadisticasPagoSeguro(),
                
                // ✅ RELACIONES OPCIONALES
                'vendedor_info' => $this->getVendedorInfoSeguro(),
            ];
            
        } catch (\Exception $e) {
            Log::error('VentasResource: Error general en toArray: ' . $e->getMessage());
            Log::error('VentasResource: Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener información de pago
     */
    private function getPagoInfoSeguro()
    {
        try {
            return [
                'valor_efectivo' => (float) ($this->valor_efectivo ?? 0),
                'valor_transferencia' => (float) ($this->valor_transferencia ?? 0),
                'valor_credito' => (float) ($this->valor_credito ?? 0),
                'valor_devuelto' => (float) ($this->valor_devuelto ?? 0),
                'total_pagado' => (float) $this->total_pagado,
                'total_pagado_efectivo' => (float) $this->total_pagado_efectivo,
                'tipo_pago' => $this->tipo_pago,
                'porcentaje_efectivo' => round($this->porcentaje_efectivo, 2),
                'porcentaje_transferencia' => round($this->porcentaje_transferencia, 2),
                'es_pago_combinado' => $this->esPagoCombinado(),
                'es_solo_efectivo' => $this->esPagoSoloEfectivo(),
                'es_solo_transferencia' => $this->esPagoSoloTransferencia(),
                'hubo_cambio' => ($this->valor_devuelto ?? 0) > 0,
                'pago_exacto' => ($this->valor_devuelto ?? 0) == 0,
                'tiene_credito' => $this->tieneCredito(),
            ];
        } catch (\Exception $e) {
            Log::error('getPagoInfoSeguro: ' . $e->getMessage());
            return [
                'valor_efectivo' => 0,
                'valor_transferencia' => 0,
                'valor_credito' => 0,
                'valor_devuelto' => 0,
                'total_pagado' => 0,
                'total_pagado_efectivo' => 0,
                'tipo_pago' => 'no_definido',
                'porcentaje_efectivo' => 0,
                'porcentaje_transferencia' => 0,
                'es_pago_combinado' => false,
                'es_solo_efectivo' => false,
                'es_solo_transferencia' => false,
                'hubo_cambio' => false,
                'pago_exacto' => true,
                'tiene_credito' => false,
            ];
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener información de crédito
     */
    private function getCreditoInfoSeguro()
    {
        try {
            if (!$this->tieneCredito()) {
                return null;
            }

            return [
                'valor_credito' => (float) $this->valor_credito,
                'credito_pagado' => $this->creditoPagado(),
                'credito_pendiente' => $this->creditoPendiente(),
                'credito_vencido' => $this->creditoVencido(),
                'fecha_credito' => $this->fecha_credito?->format('Y-m-d'),
                'fecha_promesa_pago' => $this->fecha_promesa_pago?->format('Y-m-d'),
                'dias_vencimiento' => $this->dias_vencimiento,
                'dias_restantes' => $this->dias_restantes,
                'estado_credito' => $this->getEstadoCredito(),
                'porcentaje_credito' => $this->total_pagado > 0 ? round(($this->valor_credito / $this->total_pagado) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('getCreditoInfoSeguro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener productos usando el método del modelo
     */
    private function getProductosDelModelo()
    {
        try {
            Log::info('getProductosDelModelo: Obteniendo productos_detallados del modelo');
            
            // Usar directamente el método del modelo que ya funciona
            $productosDetallados = $this->productos_detallados;
            
            Log::info('getProductosDelModelo: Tipo recibido: ' . gettype($productosDetallados));
            
            // Si es una Collection, convertir a array
            if (is_object($productosDetallados) && method_exists($productosDetallados, 'toArray')) {
                $productosArray = $productosDetallados->toArray();
                Log::info('getProductosDelModelo: Convertido Collection a array, elementos: ' . count($productosArray));
                return $productosArray;
            }
            
            // Si ya es array, devolverlo
            if (is_array($productosDetallados)) {
                Log::info('getProductosDelModelo: Ya es array, elementos: ' . count($productosDetallados));
                return $productosDetallados;
            }
            
            Log::warning('getProductosDelModelo: Tipo no reconocido, devolviendo array vacío');
            return [];
            
        } catch (\Exception $e) {
            Log::error('getProductosDelModelo: Error: ' . $e->getMessage());
            Log::error('getProductosDelModelo: Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener totales
     */
    private function getTotalesSeguro()
    {
        try {
            return [
                'cantidad_productos_diferentes' => $this->cantidad_productos_diferentes ?? 0,
                'total_productos_vendidos' => $this->total_productos ?? 0,
                'total_descuentos' => (float) ($this->total_descuentos ?? 0),
                'subtotal' => (float) (($this->precio_venta ?? 0) + ($this->total_descuentos ?? 0)),
                'total_final' => (float) ($this->precio_venta ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('getTotalesSeguro: ' . $e->getMessage());
            return [
                'cantidad_productos_diferentes' => 0,
                'total_productos_vendidos' => 0,
                'total_descuentos' => 0,
                'subtotal' => 0,
                'total_final' => 0,
            ];
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener información de variantes basado en el modelo
     */
    private function getVariantesInfoDelModelo()
    {
        try {
            Log::info('getVariantesInfoDelModelo: Iniciando');
            
            $productos = $this->getProductosDelModelo();
            
            $coloresUnicos = [];
            $tallasUnicas = [];
            $tieneVariantes = false;
            
            foreach ($productos as $index => $producto) {
                Log::info("getVariantesInfoDelModelo: Procesando producto {$index}");
                
                if (!is_array($producto)) {
                    Log::warning("getVariantesInfoDelModelo: Producto {$index} no es array");
                    continue;
                }
                
                // Buscar variante en la estructura del modelo
                if (isset($producto['variante']) && is_array($producto['variante']) && !empty($producto['variante'])) {
                    $tieneVariantes = true;
                    $variante = $producto['variante'];
                    
                    // Procesar color
                    if (isset($variante['color']) && is_array($variante['color']) && !empty($variante['color'])) {
                        $color = $variante['color'];
                        if (isset($color['id'])) {
                            $colorKey = $color['id'];
                            $coloresUnicos[$colorKey] = [
                                'id' => $color['id'],
                                'nombre' => $color['nombre'] ?? 'Sin nombre',
                                'codigo_hex' => $color['codigo_hex'] ?? null,
                            ];
                        }
                    }
                    
                    // Procesar talla
                    if (isset($variante['talla']) && is_array($variante['talla']) && !empty($variante['talla'])) {
                        $talla = $variante['talla'];
                        if (isset($talla['id'])) {
                            $tallaKey = $talla['id'];
                            $tallasUnicas[$tallaKey] = [
                                'id' => $talla['id'],
                                'nombre' => $talla['nombre'] ?? 'Sin nombre',
                                'orden' => $talla['orden'] ?? 0,
                            ];
                        }
                    }
                }
            }
            
            // Ordenar tallas por orden
            uasort($tallasUnicas, function($a, $b) {
                return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
            });
            
            $result = [
                'tiene_variantes' => $tieneVariantes,
                'colores_vendidos' => array_values($coloresUnicos),
                'tallas_vendidas' => array_values($tallasUnicas),
            ];
            
            Log::info('getVariantesInfoDelModelo: Resultado: ' . json_encode($result));
            return $result;
            
        } catch (\Exception $e) {
            Log::error('getVariantesInfoDelModelo: Error: ' . $e->getMessage());
            return [
                'tiene_variantes' => false,
                'colores_vendidos' => [],
                'tallas_vendidas' => [],
            ];
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener estadísticas de pago
     */
    private function getEstadisticasPagoSeguro()
    {
        try {
            return $this->getEstadisticasPago();
        } catch (\Exception $e) {
            Log::error('getEstadisticasPagoSeguro: ' . $e->getMessage());
            return [
                'efectivo' => ['valor' => 0, 'porcentaje' => 0, 'es_principal' => false],
                'transferencia' => ['valor' => 0, 'porcentaje' => 0, 'es_principal' => false],
                'cambio' => ['valor' => 0, 'hubo_cambio' => false],
                'resumen' => [
                    'total_venta' => 0,
                    'total_pagado' => 0,
                    'total_pagado_efectivo' => 0,
                    'tipo_pago' => 'no_definido',
                    'pago_exacto' => true,
                ]
            ];
        }
    }

    /**
     * ✅ MÉTODO SEGURO: Obtener información del vendedor
     */
    private function getVendedorInfoSeguro()
    {
        try {
            if ($this->relationLoaded('vendedor') && $this->vendedor) {
                return [
                    'id' => $this->vendedor->id,
                    'name' => $this->vendedor->name,
                    'email' => $this->vendedor->email ?? null,
                ];
            }
            return null;
        } catch (\Exception $e) {
            Log::error('getVendedorInfoSeguro: ' . $e->getMessage());
            return null;
        }
    }
}