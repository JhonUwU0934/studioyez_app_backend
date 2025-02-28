<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MontosSeeder extends Seeder
{
    public function run()
    {
        $monto = [
            'creador_id' => 1, // Reemplaza con el ID del creador
            'monto' => 1500.00, // Reemplaza con el monto deseado
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('montos')->insert($monto);
    }
}
