<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VentasSeeder extends Seeder
{
    public function run()
    {
        $ventas = [
            [
                'producto_id' => null,
                'cantidad' => 1,
                'fecha' => Carbon::now(),
                'precio_mayorista' => '38000',
                'precio_unidad' => '55000',
                'precio_venta' => '55000',
                'vendedor' => 'LINA',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'producto_id' => null,
                'cantidad' => 1,
                'fecha' => Carbon::now(),
                'precio_mayorista' => '35000',
                'precio_unidad' => '48000',
                'precio_venta' => '48000',
                'vendedor' => 'LINA',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'producto_id' => null,
                'cantidad' => 1,
                'fecha' => Carbon::now(),
                'precio_mayorista' => '33000',
                'precio_unidad' => '50000',
                'precio_venta' => '50000',
                'vendedor' => 'LINA',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('ventas')->insert($ventas);
    }
}
