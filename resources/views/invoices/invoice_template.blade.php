<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Studio Yez</title>
    <style>
        @page {
            size: 8cm 297mm;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
        }

        .invoice {
            max-width: 100%;
            border: 2px solid #333;
            padding: 15px;
            margin: 0 auto;
            background: white;
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #333;
            letter-spacing: 1px;
        }

        .header .instagram {
            font-size: 12px;
            margin: 5px 0;
            color: #666;
        }

        .header .address {
            font-size: 11px;
            margin: 8px 0 0 0;
            color: #555;
            line-height: 1.3;
        }

        /* Separator */
        .separator {
            text-align: center;
            margin: 15px 0;
            font-weight: bold;
            color: #333;
        }

        /* Info Sections */
        .info-section {
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            padding: 3px 0;
        }

        .info-row .label {
            font-weight: bold;
            color: #333;
            min-width: 40%;
        }

        .info-row .value {
            color: #555;
            text-align: right;
            flex: 1;
        }

        /* Table Styles */
        .table-container {
            margin: 15px 0;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .products-table th {
            background-color: #f5f5f5;
            border: 1px solid #333;
            padding: 8px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .products-table td {
            border: 1px solid #333;
            padding: 6px 4px;
            font-size: 10px;
            vertical-align: top;
        }

        .product-details {
            line-height: 1.2;
        }

        .product-name {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .product-info {
            color: #666;
            font-size: 9px;
        }

        .price-cell {
            text-align: right;
            font-weight: bold;
            min-width: 60px;
        }

        /* Totals Section */
        .totals-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #333;
        }

        .totals-header {
            text-align: center;
            margin-bottom: 15px;
        }

        .totals-header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
            color: #333;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table th {
            background-color: #333;
            color: white;
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }

        .totals-table td {
            border: 1px solid #333;
            padding: 8px;
            font-size: 12px;
        }

        .total-amount {
            text-align: right;
            font-weight: bold;
            font-size: 14px;
            background-color: #f9f9f9;
        }

        /* Responsive adjustments */
        @media print {
            body {
                font-size: 12px;
            }
            
            .invoice {
                border: 1px solid #333;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <h1>STUDIO YEZ</h1>
            <div class="instagram">Instagram: @studioyez</div>
            <div class="address">
                C.C El Caleño<br>
                Cl. 13 # 9 - 35, Cali, Valle del Cauca
            </div>
        </div>

        <!-- Invoice Info -->
        <div class="info-section">
            <div class="info-row">
                <span class="label">Factura No:</span>
                <span class="value">{{$venta->id}}</span>
            </div>
            <div class="info-row">
                <span class="label">Fecha:</span>
                <span class="value">{{$venta->created_at}}</span>
            </div>
            <div class="info-row">
                <span class="label">Cliente:</span>
                <span class="value">{{$venta->nombre_comprador}}</span>
            </div>
        </div>

        <div class="separator">═══════════════════════════════</div>

        <!-- Vendor Info -->
        <div class="info-section">
            <div class="info-row">
                <span class="label">Vendedor:</span>
                <span class="value">{{$venta->vendedor}}</span>
            </div>
            <div class="info-row">
                <span class="label">Código Factura:</span>
                <span class="value">{{$venta->codigo_factura}}</span>
            </div>
        </div>

        <div class="separator">═══════════════════════════════</div>

        <!-- Products Table -->
        <div class="table-container">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>DESCRIPCIÓN</th>
                        <th>CANT.</th>
                        <th>PRECIO</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($venta['productos'] as $producto)
                    <tr>
                        <td>
                            <div class="product-details">
                                <div class="product-name">{{$producto->denominacion}}</div>
                                <div class="product-info">Código: {{$producto->codigo}}</div>
                            </div>
                        </td>
                        <td style="text-align: center;">{{$producto->cantidad}}</td>
                        <td class="price-cell">${{number_format($producto->precio_por_unidad, 0, ',', '.')}}</td>
                        <td class="price-cell">${{number_format($producto->total_producto, 0, ',', '.')}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-header">
                <h2>VALORES TOTALES</h2>
                <h2>═══ STUDIO YEZ ═══</h2>
            </div>
            
            <table class="totals-table">
                <thead>
                    <tr>
                        <th colspan="2">CONCEPTO</th>
                        <th>VALOR TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="2" style="font-weight: bold;">TOTAL A PAGAR</td>
                        <td class="total-amount">${{number_format($venta->precio_venta, 0, ',', '.')}}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="separator" style="margin-top: 20px;">═══════════════════════════════</div>
        
        <div style="text-align: center; margin-top: 15px; font-size: 11px; color: #666;">
            <div>¡Gracias por su compra!</div>
            <div style="margin-top: 5px;">Studio Yez - Tu estilo, nuestra pasión</div>
        </div>
    </div>
</body>
</html>