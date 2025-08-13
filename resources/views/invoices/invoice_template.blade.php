<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Studio Yez - POS</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 5mm;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.2;
            color: black;
            width: 80mm;
            background: white;
        }

        .invoice {
            width: 100%;
            max-width: 70mm;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 5px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
            letter-spacing: 1px;
        }

        .header .contact {
            font-size: 10px;
            margin: 2px 0;
        }

        .header .address {
            font-size: 9px;
            margin: 3px 0;
            line-height: 1.1;
        }

        /* Separator */
        .separator {
            text-align: center;
            margin: 6px 0;
            font-size: 10px;
        }

        /* Info rows */
        .info-row {
            margin: 3px 0;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        .info-row .label {
            font-weight: bold;
        }

        .info-row .value {
            text-align: right;
            max-width: 50%;
            word-wrap: break-word;
        }

        /* Products section */
        .products-header {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0 5px 0;
            padding: 2px 0;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }

        .product-item {
            margin: 5px 0;
            padding: 3px 0;
            border-bottom: 1px dashed black;
        }

        .product-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .product-details {
            font-size: 10px;
            margin: 1px 0;
        }

        .product-total {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            margin-top: 2px;
        }

        /* Totals section */
        .totals-section {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 2px solid black;
        }

        .totals-header {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            padding: 3px 0;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
        }

        .total-amount {
            font-size: 14px;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
            line-height: 1.2;
        }

        /* Utility classes */
        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
        }

        .small {
            font-size: 9px;
        }

        /* Print specific */
        @media print {
            body {
                padding: 2mm;
            }
            
            .invoice {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- Header -->
        <div class="header">
            <h1>STUDIO YEZ</h1>
            <div class="contact">Instagram: @studioyez</div>
            <div class="address">
                C.C El Caleño<br>
                Cl. 13 # 9-35, Cali<br>
                Valle del Cauca
            </div>
        </div>

        <div class="separator">================================</div>

        <!-- Invoice Info -->
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

        <div class="separator">================================</div>

        <div class="info-row">
            <span class="label">Vendedor:</span>
            <span class="value">{{$venta->vendedor}}</span>
        </div>
        
        <div class="info-row">
            <span class="label">Código:</span>
            <span class="value">{{$venta->codigo_factura}}</span>
        </div>

        <div class="separator">================================</div>

        <!-- Products -->
        <div class="products-header">
            PRODUCTOS
        </div>

        @foreach ($venta['productos'] as $producto)
        <div class="product-item">
            <div class="product-name">{{$producto->denominacion}}</div>
            <div class="product-details">Código: {{$producto->codigo}}</div>
            <div class="product-details">
                Cantidad: {{$producto->cantidad}} x ${{number_format($producto->precio_por_unidad, 0, ',', '.')}}
            </div>
            <div class="product-total">
                Total: ${{number_format($producto->total_producto, 0, ',', '.')}}
            </div>
        </div>
        @endforeach

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-header">
                VALORES TOTALES<br>
                *** STUDIO YEZ ***
            </div>
            
            <div class="total-row">
                <span>TOTAL A PAGAR:</span>
                <span class="total-amount">${{number_format($venta->precio_venta, 0, ',', '.')}}</span>
            </div>
        </div>

        <div class="separator">================================</div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="bold">¡Gracias por su compra!</div>
            <div>Studio Yez</div>
            <div>Tu estilo, nuestra pasión</div>
        </div>
    </div>
</body>
</html>