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
     *
     */
    public function createInvoice(Request $request)
    {
        $id = $request->get('id');

        $venta = Ventas::findOrFail($id);

        // Obtener los productos asociados a la venta
        $productos = DB::table('productos')
            ->join('venta_producto', 'productos.id', '=', 'venta_producto.id_producto')
            ->where('venta_producto.id_venta', $venta->id)
            ->select('productos.*', 'venta_producto.cantidad', 'venta_producto.total_producto')
            ->get();

        $venta->productos = $productos;

        // Generar la factura en PDF utilizando dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $html = view('invoices.invoice_template', ['venta' => $venta]); // Aquí debes crear una vista blade con el diseño de la factura
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
        $fileName = 'invoice_' . $venta->id . '.pdf';
        $pdfPath = public_path('invoices/' . $fileName);
        file_put_contents($pdfPath, $pdfOutput);

        // Almacenar la URL de la factura en la columna "url_factura"
        $pdfUrl = asset('invoices/' . $fileName);

        \DB::connection('mysql')
            ->table('ventas')
            ->where('id',$id)
            ->update([
                'estado_pago' => 1,
                'url_factura' =>$pdfUrl
            ]);

        return response()->json(['message' => 'Factura creada y venta finalizada correctamente', 'pdf_url' => $pdfUrl]);
    }


}
