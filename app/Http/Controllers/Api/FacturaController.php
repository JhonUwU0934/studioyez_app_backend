<?php

namespace App\Http\Controllers\Api;

use App\Models\Producto;
use App\Models\VentaProducto;
use App\Models\Ventas;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\VentasResource;
use Illuminate\Support\Facades\DB;
use Dompdf\Dompdf;
use Dompdf\Options;

class FacturaController extends Controller
{
    /**
     * Crear factura con información completa de pago combinado y variantes
     */
    public function createInvoice(Request $request)
    {
        $id = $request->get('id');

        $venta = Ventas::findOrFail($id);

        // ✅ ACTUALIZADO: Obtener productos con información completa de variantes
        $productos = DB::table('productos')
            ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
            ->leftJoin('producto_variantes', 'venta_producto.id_producto_variante', '=', 'producto_variantes.id')
            ->leftJoin('colores', 'producto_variantes.color_id', '=', 'colores.id')
            ->leftJoin('tallas', 'producto_variantes.talla_id', '=', 'tallas.id')
            ->where('venta_producto.id_venta', $venta->id)
            ->select([
                // Información del producto
                'productos.id as producto_id',
                'productos.codigo',
                'productos.denominacion',
                'productos.imagen as producto_imagen',
                
                // Información de la venta
                'venta_producto.cantidad',
                'venta_producto.total_producto',
                'venta_producto.descuento',
                'venta_producto.sku_vendido',
                'venta_producto.precio_unitario_vendido',
                
                // Información de la variante
                'producto_variantes.id as variante_id',
                'producto_variantes.sku as variante_sku',
                'producto_variantes.imagen_variante',
                
                // Información del color
                'colores.id as color_id',
                'colores.nombre as color_nombre',
                'colores.codigo_hex as color_codigo',
                
                // Información de la talla
                'tallas.id as talla_id',
                'tallas.nombre as talla_nombre',
                'tallas.orden as talla_orden',
            ])
            ->get();

        $venta->productos = $productos;

        // ✅ NUEVO: Calcular totales y estadísticas para la factura
        $venta->estadisticas = $this->calcularEstadisticasFactura($venta, $productos);

        // Generar la factura en PDF utilizando dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans'); // ✅ Mejor fuente para caracteres especiales

        $dompdf = new Dompdf($options);
        $html = view('invoices.invoice_template', ['venta' => $venta]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfOutput = $dompdf->output();

        // Ruta completa al directorio donde se guardarán los PDFs
        $pdfDirectory = public_path('invoices');

        // Verificar si el directorio no existe y crearlo si es necesario
        if (!file_exists($pdfDirectory)) {
            mkdir($pdfDirectory, 0777, true);
        }

        // Guardar el PDF generado
        $fileName = 'invoice_' . $venta->codigo_factura . '_' . date('Y-m-d_H-i-s') . '.pdf';
        $pdfPath = public_path('invoices/' . $fileName);
        file_put_contents($pdfPath, $pdfOutput);

        // Almacenar la URL de la factura en la columna "url_factura"
        $pdfUrl = asset('invoices/' . $fileName);

        \DB::connection('mysql')
            ->table('ventas')
            ->where('id', $id)
            ->update([
                'estado_pago' => 1,
                'url_factura' => $pdfUrl
            ]);

        return response()->json([
            'message' => 'Factura creada y venta finalizada correctamente',
            'pdf_url' => $pdfUrl,
            'codigo_factura' => $venta->codigo_factura,
            'estadisticas' => $venta->estadisticas
        ]);
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
            'fecha_formateada' => $venta->created_at->format('d/m/Y H:i:s'),
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