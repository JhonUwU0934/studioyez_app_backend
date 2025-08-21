<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Studio Yez - {{ $venta->codigo_factura }}</title>
    <style>
        @page {
            size: 8cm 297mm;
            margin: 0;
        }
        
        * {
            box-sizing: border-box;
            font-weight: bold;
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
            font-weight: bold;
        }

        .invoice {
            width: 100%;
            max-width: 70mm;
            font-weight: bold;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 5px;
            font-weight: bold;
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
            font-weight: bold;
        }

        .header .address {
            font-size: 9px;
            margin: 3px 0;
            line-height: 1.1;
            font-weight: bold;
        }

        /* Separator */
        .separator {
            text-align: center;
            margin: 6px 0;
            font-size: 10px;
            font-weight: bold;
        }

        .double-separator {
            text-align: center;
            margin: 8px 0;
            font-size: 10px;
            font-weight: bold;
        }

        /* Info rows */
        .info-row {
            margin: 3px 0;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
            font-weight: bold;
        }

        .info-row .label {
            font-weight: bold;
        }

        .info-row .value {
            text-align: right;
            max-width: 50%;
            word-wrap: break-word;
            font-weight: bold;
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
            border-bottom: 1px dashed #ccc;
            font-weight: bold;
        }

        .product-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .product-details {
            font-size: 10px;
            margin: 1px 0;
            font-weight: bold;
            color: #333;
        }

        .product-variant {
            font-size: 9px;
            color: #666;
            font-weight: bold;
            margin: 1px 0;
        }

        .product-pricing {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            margin: 2px 0;
            font-weight: bold;
        }

        .product-total {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            margin-top: 2px;
        }

        /* Payment section */
        .payment-section {
            margin: 10px 0;
            padding: 5px 0;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            font-weight: bold;
        }

        .payment-header {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            font-weight: bold;
            margin: 2px 0;
        }

        .payment-type {
            font-size: 10px;
            text-align: center;
            margin: 3px 0;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Totals section */
        .totals-section {
            margin-top: 10px;
            padding-top: 5px;
            border-top: 2px solid black;
            font-weight: bold;
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
            font-size: 11px;
            font-weight: bold;
            padding: 2px 0;
            margin: 1px 0;
        }

        .total-final {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: bold;
            padding: 3px 0;
            border-top: 1px solid black;
            border-bottom: 1px solid black;
            margin: 3px 0;
        }

        .total-amount {
            font-size: 14px;
            font-weight: bold;
        }

        /* Change section */
        .change-section {
            margin: 8px 0;
            padding: 5px 0;
            text-align: center;
            background-color: #f0f0f0;
            border: 1px dashed black;
            font-weight: bold;
        }

        .change-amount {
            font-size: 14px;
            font-weight: bold;
            margin: 2px 0;
        }

        /* Summary section */
        .summary-section {
            margin: 8px 0;
            padding: 5px 0;
            border-top: 1px dashed #ccc;
            border-bottom: 1px dashed #ccc;
            font-size: 10px;
            font-weight: bold;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 1px 0;
            font-weight: bold;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
            line-height: 1.2;
            font-weight: bold;
        }

        .footer-message {
            margin: 3px 0;
            font-weight: bold;
        }

        /* Utility classes */
        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
            font-weight: bold;
        }

        .small {
            font-size: 9px;
            font-weight: bold;
        }

        .highlight {
            background-color: #f5f5f5;
            padding: 2px;
            font-weight: bold;
        }

        /* Print specific */
        @media print {
            body {
                padding: 2mm;
                font-weight: bold;
            }
            
            .invoice {
                max-width: none;
                font-weight: bold;
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
            <div class="contact">WhatsApp: +57 300 123 4567</div>
            <div class="address">
                C.C El Caleño<br>
                Cl. 13 # 9-35, Cali<br>
                Valle del Cauca
            </div>
        </div>

        <div class="double-separator">================================</div>

        <!-- Invoice Info -->
        <div class="info-row">
            <span class="label">Factura No:</span>
            <span class="value">{{ $venta->id }}</span>
        </div>
        
        <div class="info-row">
            <span class="label">Código:</span>
            <span class="value">{{ $venta->codigo_factura }}</span>
        </div>
        
        <div class="info-row">
            <span class="label">Fecha:</span>
            <span class="value">{{ $venta->estadisticas['fecha_formateada'] }}</span>
        </div>
        
        <div class="info-row">
            <span class="label">Cliente:</span>
            <span class="value">{{ $venta->nombre_comprador }}</span>
        </div>

        @if($venta->numero_comprador)
        <div class="info-row">
            <span class="label">Teléfono:</span>
            <span class="value">{{ $venta->numero_comprador }}</span>
        </div>
        @endif

        <div class="separator">================================</div>

        <div class="info-row">
            <span class="label">Vendedor:</span>
            <span class="value">{{ $venta->vendedor }}</span>
        </div>

        <div class="separator">================================</div>

        <!-- Products -->
        <div class="products-header">
            PRODUCTOS VENDIDOS
        </div>

        @foreach ($venta->productos as $producto)
        <div class="product-item">
            <div class="product-name">{{ $producto->denominacion }}</div>
            <div class="product-details">Código: {{ $producto->codigo }}</div>
            
            @if($producto->variante_sku)
            <div class="product-variant">SKU: {{ $producto->variante_sku }}</div>
            @endif
            
            @if($producto->color_nombre || $producto->talla_nombre)
            <div class="product-variant">
                @if($producto->color_nombre)Color: {{ $producto->color_nombre }}@endif
                @if($producto->color_nombre && $producto->talla_nombre) | @endif
                @if($producto->talla_nombre)Talla: {{ $producto->talla_nombre }}@endif
            </div>
            @endif
            
            <div class="product-pricing">
                <span>{{ $producto->cantidad }} x ${{ number_format($producto->precio_unitario_vendido, 0, ',', '.') }}</span>
                @if($producto->descuento > 0)
                <span>Desc: -${{ number_format($producto->descuento, 0, ',', '.') }}</span>
                @endif
            </div>
            
            <div class="product-total">
                Total: ${{ number_format($producto->total_producto, 0, ',', '.') }}
            </div>
        </div>
        @endforeach

        <!-- Summary -->
        <div class="summary-section">
            <div class="summary-row">
                <span>Productos diferentes:</span>
                <span>{{ $venta->estadisticas['total_productos'] }}</span>
            </div>
            <div class="summary-row">
                <span>Cantidad total:</span>
                <span>{{ $venta->estadisticas['cantidad_total'] }}</span>
            </div>
            @if($venta->estadisticas['tiene_descuentos'])
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>${{ number_format($venta->estadisticas['subtotal'], 0, ',', '.') }}</span>
            </div>
            <div class="summary-row">
                <span>Descuentos:</span>
                <span>-${{ number_format($venta->estadisticas['total_descuentos'], 0, ',', '.') }}</span>
            </div>
            @endif
        </div>

        <!-- Payment Information -->
        <div class="payment-section">
            <div class="payment-header">INFORMACIÓN DE PAGO</div>
            
            <div class="payment-type">{{ $venta->estadisticas['tipo_pago'] }}</div>
            
            @if($venta->estadisticas['valor_efectivo'] > 0)
            <div class="payment-row">
                <span>💵 Efectivo:</span>
                <span>${{ number_format($venta->estadisticas['valor_efectivo'], 0, ',', '.') }}</span>
            </div>
            @endif
            
            @if($venta->estadisticas['valor_transferencia'] > 0)
            <div class="payment-row">
                <span>💳 Transferencia:</span>
                <span>${{ number_format($venta->estadisticas['valor_transferencia'], 0, ',', '.') }}</span>
            </div>
            @endif
            
            <div class="payment-row">
                <span>Total pagado:</span>
                <span>${{ number_format($venta->estadisticas['total_pagado'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-header">
                TOTAL DE LA VENTA<br>
                *** STUDIO YEZ ***
            </div>
            
            <div class="total-final">
                <span>TOTAL A PAGAR:</span>
                <span class="total-amount">${{ number_format($venta->precio_venta, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($venta->estadisticas['hubo_cambio'])
        <!-- Change -->
        <div class="change-section">
            <div class="small">CAMBIO DEVUELTO</div>
            <div class="change-amount">${{ number_format($venta->estadisticas['valor_devuelto'], 0, ',', '.') }}</div>
        </div>
        @endif

        <div class="double-separator">================================</div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-message bold">¡Gracias por su compra!</div>
            <div class="footer-message">Studio Yez</div>
            <div class="footer-message">Tu estilo, nuestra pasión</div>
            
            <div class="separator">- - - - - - - - - - - - - - - - -</div>
            
            <div class="small">
                @if($venta->url_factura)
                Factura digital disponible<br>
                @endif
                Síguenos en redes sociales<br>
                Instagram: @studioyez<br>
                Para consultas: +57 300 123 4567
            </div>
            
            <div class="separator small">{{ now()->format('d/m/Y H:i:s') }}</div>
        </div>
    </div>
</body>
</html>