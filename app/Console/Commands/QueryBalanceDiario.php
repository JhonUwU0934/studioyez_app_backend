<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Balance;

class QueryBalanceDiario extends Command
{
    protected $signature = 'query:ejecutar';

    protected $description = 'Ejecutar el query diario';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Obtener la fecha actual
        $fechaActual = now()->toDateString();
        // Ejecutar el primer query y obtener los resultados
        $ventas = DB::table('ventas')
            ->whereDate('fecha', '=', $fechaActual)
            ->selectRaw('SUM(precio_venta) as total_precio_venta, SUM(cantidad) as total_cantidad')
            ->first();

        // Ejecutar el segundo query y obtener los resultados
        $gastos = DB::table('gastos')
            ->whereDate('fecha', '=', $fechaActual)
            ->selectRaw('SUM(monto) as total_gastos')
            ->first();

        // Calcular el total usando los resultados de los queries
        $total = $ventas->total_precio_venta + 200000 - $gastos->total_gastos;

        // Insertar los resultados en la tabla balances
        $balance = new Balance;
        $balance->ventas_diarias = $ventas->total_precio_venta;
        $balance->gastos_diarios = $gastos->total_gastos;
        $balance->cantidad_ventas = $ventas->total_cantidad;
        $balance->monto_inicial = 200000;
        $balance->total = $total;
        $balance->save();

        // Mostrar un mensaje informativo
        $this->info('Balance diario calculado y almacenado en la tabla balances.');
    }
}
