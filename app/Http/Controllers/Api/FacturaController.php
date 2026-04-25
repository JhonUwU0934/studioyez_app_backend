<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use App\Models\VentaProducto;
use App\Models\Ventas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\VentasResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\Options;

class FacturaController extends Controller
{
    /**
     * Crear factura con información completa de pago combinado y variantes
     */
    public function createInvoice(Request $request)
    {
        try {
            $id = $request->get('id');

            if (!$id) {
                return response()->json([
                    'success' => false,
                    'error' => 'ID de venta no proporcionado'
                ], 422);
            }

            $venta = Ventas::find($id);
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'error' => 'Venta no encontrada (ID: ' . $id . ')'
                ], 404);
            }

            // Obtener productos con info de variantes
            $productos = DB::table('productos')
                ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
                ->leftJoin('producto_variantes', 'venta_producto.id_producto_variante', '=', 'producto_variantes.id')
                ->leftJoin('colores', 'producto_variantes.color_id', '=', 'colores.id')
                ->leftJoin('tallas', 'producto_variantes.talla_id', '=', 'tallas.id')
                ->where('venta_producto.id_venta', $venta->id)
                ->select([
                    'productos.id as producto_id',
                    'productos.codigo',
                    'productos.denominacion',
                    'productos.imagen as producto_imagen',
                    'venta_producto.cantidad',
                    'venta_producto.total_producto',
                    'venta_producto.descuento',
                    'venta_producto.sku_vendido',
                    'venta_producto.precio_unitario_vendido',
                    'producto_variantes.id as variante_id',
                    'producto_variantes.sku as variante_sku',
                    'producto_variantes.imagen_variante',
                    'colores.id as color_id',
                    'colores.nombre as color_nombre',
                    'colores.codigo_hex as color_codigo',
                    'tallas.id as talla_id',
                    'tallas.nombre as talla_nombre',
                    'tallas.orden as talla_orden',
                ])
                ->get();

            if ($productos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'error' => 'La venta no tiene productos asociados'
                ], 422);
            }

            $venta->productos = $productos;
            $venta->estadisticas = $this->calcularEstadisticasFactura($venta, $productos);

            // Verificar y crear directorio con permisos
            $pdfDirectory = public_path('invoices');
            if (!file_exists($pdfDirectory)) {
                if (!@mkdir($pdfDirectory, 0775, true) && !is_dir($pdfDirectory)) {
                    Log::error('No se pudo crear directorio invoices: ' . $pdfDirectory);
                    return response()->json([
                        'success' => false,
                        'error' => 'No se pudo crear el directorio de facturas. Verifica permisos.'
                    ], 500);
                }
            }
            if (!is_writable($pdfDirectory)) {
                Log::error('Directorio invoices no escribible: ' . $pdfDirectory);
                return response()->json([
                    'success' => false,
                    'error' => 'El directorio de facturas no tiene permisos de escritura.'
                ], 500);
            }

            // Generar PDF
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);

            $dompdf = new Dompdf($options);

            try {
                $html = view('invoices.invoice_template', ['venta' => $venta])->render();
            } catch (\Throwable $e) {
                Log::error('Error renderizando template invoice: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error' => 'Error en el template de factura',
                    'message' => $e->getMessage()
                ], 500);
            }

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');

            try {
                $dompdf->render();
                $pdfOutput = $dompdf->output();
            } catch (\Throwable $e) {
                Log::error('Error generando PDF con DomPDF: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error' => 'Error generando el PDF',
                    'message' => $e->getMessage()
                ], 500);
            }

            // Guardar PDF
            $fileName = 'invoice_' . $venta->codigo_factura . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdfPath = $pdfDirectory . DIRECTORY_SEPARATOR . $fileName;
            $bytesWritten = @file_put_contents($pdfPath, $pdfOutput);

            if ($bytesWritten === false) {
                Log::error('No se pudo escribir el PDF en: ' . $pdfPath);
                return response()->json([
                    'success' => false,
                    'error' => 'No se pudo guardar el archivo PDF'
                ], 500);
            }

            $pdfUrl = asset('invoices/' . $fileName);

            // Actualizar venta
            DB::table('ventas')
                ->where('id', $id)
                ->update([
                    'estado_pago' => 1,
                    'url_factura' => $pdfUrl
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Factura creada y venta finalizada correctamente',
                'pdf_url' => $pdfUrl,
                'codigo_factura' => $venta->codigo_factura,
                'estadisticas' => $venta->estadisticas
            ]);

        } catch (\Throwable $e) {
            Log::error('Error inesperado en createInvoice: ' . $e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile());
            return response()->json([
                'success' => false,
                'error' => 'Error inesperado al crear factura',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Calcular estadísticas para la factura
     */
    private function calcularEstadisticasFactura($venta, $productos)
    {
        $totalProductos = $productos->count();
        $cantidadTotal = $productos->sum('cantidad');
        $totalDescuentos = $productos->sum('descuento');
        $subtotal = $productos->sum('total_producto') + $totalDescuentos;

        // Información de pago
        $totalPagado = ($venta->valor_efectivo ?? 0) + ($venta->valor_transferencia ?? 0) + ($venta->valor_credito ?? 0);
        $valorDevuelto = $venta->valor_devuelto ?? 0;

        // Tipo de pago
        $tipoPago = 'Contado';
        $metodos = [];
        
        if ($venta->valor_efectivo > 0) $metodos[] = 'Efectivo';
        if ($venta->valor_transferencia > 0) $metodos[] = 'Transferencia';
        if (!is_null($venta->valor_credito) && $venta->valor_credito > 0) {
            $estadoCredito = $venta->credito_pagado === 1 ? 'Pagado' : 'Pendiente';
            $metodos[] = "Crédito ({$estadoCredito})";
        }
        
        if (count($metodos) > 1) {
            $tipoPago = 'Pago Combinado: ' . implode(' + ', $metodos);
        } elseif (count($metodos) == 1) {
            $tipoPago = $metodos[0];
        }

        return [
            'total_productos' => $totalProductos,
            'cantidad_total' => $cantidadTotal,
            'subtotal' => $subtotal,
            'total_descuentos' => $totalDescuentos,
            'total_final' => $venta->precio_venta,
            'total_pagado' => $totalPagado,
            'valor_devuelto' => $valorDevuelto,
            'tipo_pago' => $tipoPago,
            'valor_efectivo' => $venta->valor_efectivo ?? 0,
            'valor_transferencia' => $venta->valor_transferencia ?? 0,
            'fecha_formateada' => $venta->created_at ? \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y H:i:s') : date('d/m/Y H:i:s'),
            'tiene_variantes' => $productos->whereNotNull('variante_id')->count() > 0,
            'tiene_descuentos' => $totalDescuentos > 0,
            'hubo_cambio' => $valorDevuelto > 0,
        ];
    }

    /**
     * ✅ NUEVO: Método para previsualizar factura sin generar PDF
     */
    public function previewInvoice(Request $request)
    {
        $id = $request->get('id');
        $venta = Ventas::findOrFail($id);

        // Obtener productos con información completa
        $productos = DB::table('productos')
            ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
            ->leftJoin('producto_variantes', 'venta_producto.id_producto_variante', '=', 'producto_variantes.id')
            ->leftJoin('colores', 'producto_variantes.color_id', '=', 'colores.id')
            ->leftJoin('tallas', 'producto_variantes.talla_id', '=', 'tallas.id')
            ->where('venta_producto.id_venta', $venta->id)
            ->select([
                'productos.*',
                'venta_producto.cantidad',
                'venta_producto.total_producto',
                'venta_producto.descuento',
                'venta_producto.sku_vendido',
                'venta_producto.precio_unitario_vendido',
                'producto_variantes.sku as variante_sku',
                'colores.nombre as color_nombre',
                'colores.codigo_hex as color_codigo',
                'tallas.nombre as talla_nombre',
            ])
            ->get();

        $venta->productos = $productos;
        $venta->estadisticas = $this->calcularEstadisticasFactura($venta, $productos);

        return view('invoices.invoice_template', ['venta' => $venta]);
    }
}