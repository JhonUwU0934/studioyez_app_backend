<style>
    @page {
            size: 8cm 297mm; /* Tamaño personalizado */
            margin: 0; /* Sin márgenes */
        }
        body {
            margin: 0;
            padding: 0;
            font-size: 16px;
        }

h1,
h2,
p,
th,
td {
    color: black !important;
    font-weight: bold !important;
}
.invoice {
    max-width: 100%;
    border: 1px solid black;
	 font-size: 17px;
     padding: 12px;
     margin: 0 auto;
}
 .invoice .separator {
	 margin: 10px 0;
	 text-align: center;
}
 .invoice .separator p {
	 margin: 0;
}
 .invoice .page-header .page-header__title {
	 text-align: center;
}
 .invoice .page-header .page-header__title h1 {
	 font-size: 1.5em;
}
 .invoice .page-header .page-header__adres, .invoice .page-header .page-header__store, .invoice .page-header .page-header__nit {
	 text-align: center;
	 margin-bottom: 2px;
}
 .invoice .page-header .page-header__adres h2, .invoice .page-header .page-header__store h2, .invoice .page-header .page-header__nit h2 {
	 font-size: 1em;
	 margin: 0;
	 line-height: 1;
}
 .invoice .page-header .page-header__adres h2 span, .invoice .page-header .page-header__store h2 span, .invoice .page-header .page-header__nit h2 span {
	 font-size: 1em;
}
 .invoice .page-info .item__page-info p {
	 font-size: 0.9em;
	 margin: 0;
	 line-height: 1.3;
}
 .invoice .page-info .item__page-info p span {
	 font-size: 1em;
	 text-transform: capitalize;
	 font-weight: 500;
}
 .invoice .page-data .item__page-info p {
	 margin: 0;
	 font-size: 0.9em;
	 line-height: 1.2;
}
 .invoice .page-data .item__page-info p span {
	 font-size: 1em;
}
 .invoice .page-description thead th {
	 text-align: center;
	 font-size: 0.9em;
}
 .invoice .page-description tbody tr td {
	 font-size: 0.9em;
}
 .invoice .page-description tbody tr td p {
	 margin: 0;
	 font-size: 0.9em;
	 line-height: 1.3;
}
 .invoice .page-warranty .item__page-info p {
	 margin: 0;
	 text-align: center;
	 font-size: 0.9em;
	 line-height: 1.2;
}
 .invoice .page-warranty .item__page-info.item__page-info--title p {
	 font-size: 1.1em;
}
 .invoice .page-raisedThirdParties .page-raisedThirdParties__title {
	 text-align: center;
}
 .invoice .page-raisedThirdParties .page-raisedThirdParties__title h1 {
	 font-size: 1em;
}
 .invoice .page-raisedThirdParties .page-raisedThirdParties__title h2 {
	 font-size: 1em;
}

</style>

<div class="invoice">
    <!--  header  -->
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <div class="page-header__title">
                    <h1>Studio Yez.</h1>
                </div>
                <div class="page-header__nit">
                    <h2><span>Instagram: </span>studioyez</h2>
                </div>
                <div class="page-header__adres">
                    <h2>C.C El Caleño
                        Cl. 13 # 9 - 35, Cali, Valle del Cauca</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="separator">
        <p>*****************************</p>
    </div>

    <!--  Info  -->
    <div class="row">
        <div class="col-12">
            <div class="page-info">
                <div class="item__page-info">
                    <p><span>Factura De Venta No. </span>{{$venta->id}}</p>
                </div>
                <div class="item__page-info">
                    <p><span>Fecha: </span>{{$venta->created_at}}</p>
                </div>
                <div class="item__page-info">
                    <p><span>Cliente: </span>{{$venta->nombre_comprador}}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="separator">
        <p>*****************************</p>
    </div>

    <!--  Data  -->
    <div class="row">
        <div class="col-12">
            <div class="page-data">
                <div class="item__page-info">
                    <p><span>Vendedor: </span>{{$venta->vendedor}}</p>
                </div>
                <div class="item__page-info">
                    <p><span>Codigo De Factura: </span>{{$venta->codigo_factura}}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="separator">
        <p>*****************************</p>
    </div>

    <!--  table  -->
    <div class="row">
        <div class="col-12">
            <div class="page-description">
                <center>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th scope="col">DESCRIPCION</th>
                            <th scope="col"></th>
                            <th scope="col"></th>
                            <th scope="col">VALOR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($venta['productos'] as $producto)
                            <tr>
                                <td>
                                    <p>{{$producto->denominacion}}</p>
                                    <p>{{ $producto->precio_por_unidad }}</p>
                                    <p>{{ $producto->cantidad }}</p>
                                    <p>{{ $producto->codigo }}</p>
                                    <!-- ... Otros detalles del producto ... -->
                                </td>
                                <td></td>
                                <td></td>
                                <td class="text-right">{{ $producto->total_producto }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </center>

            </div>
        </div>
    </div>
 <!--   <div class="separator">
        <p>*****************************</p>
    </div>

      warranty

    <div class="row">
        <div class="col-12">
            <div class="page-warranty">
                <div class="item__page-info item__page-info--title">
                    <p>GARANTIA DE EQUIPO UN ANO</p>
                </div>
                <div class="item__page-info">
                    <p>No cubre golpes ni humedad, sitios donde puede hacer efectiva su garantia</p>
                </div>
                <div class="item__page-info">
                    <p>Cll 93B No 16-52/58,PBX 4822222</p>
                    <p>CLL 96 No. 11B-39. 5966430</p>
                </div>
                <div class="item__page-info">
                    <p>GARANTIA DE ACCESORIO TRES MESES; No cubre golpes ni humedad, ni maltrato.</p>
                </div>
            </div>
        </div>
    </div>
    -->

    <div class="separator">
        <p>*****************************</p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="page-raisedThirdParties">
                <div class="page-raisedThirdParties__title">
                    <h1>VALORES TOTALES</h1>
                    <h2>== ==== STUDIO YEZ ==== ==</h2>
                </div>
            </div>
        </div>
    </div>

    <!--  table  -->
    <div class="row">
        <div class="col-12">
          <div class="page-description">
            <table class="table table-sm">
            <thead>
              <tr>
                <th colspan="2" scope="col">CONCEPTO</th>
                <th scope="col">VALOR TOTAL</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="2"></td>
                <td class="text-right">{{$venta->precio_venta}}</td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>
    </div>

</div>
