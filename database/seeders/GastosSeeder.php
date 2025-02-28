<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GastosSeeder extends Seeder
{
    public function run()
    {
        $gastos = [
            [
                'descripcion' => 'Pago de facturas',
                'monto' => 150000,
                'fecha' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'descripcion' => 'Compra de suministros',
                'monto' => 50000,
                'fecha' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'descripcion' => 'Gastos de oficina',
                'monto' => 30000,
                'fecha' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('gastos')->insert($gastos);
    }
}
