<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportProductsDataExcel extends Command
{
    protected $signature = 'import:excel {file}';

    protected $description = 'Import data from Excel file';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error('El archivo Excel no existe.');
            return;
        }

        // Cargar el archivo Excel
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($worksheet->getRowIterator() as $row) {
            $data = [];

            foreach ($row->getCellIterator() as $cell) {
                $data[] = $cell->getValue();
            }

            // Insertar los datos en la tabla 'productos'
            DB::table('productos')->insert([
                'codigo' => $data[0],
                'denominacion' => $data[1],
                'imagen' => null, // Puedes ajustar esto según necesites
                'existente_en_almacen' => $data[4],
                'precio_por_mayor' => $this->parsePrecio($data[2]),
                'precio_por_unidad' => $this->parsePrecio($data[3]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->info('Los datos se han importado correctamente.');
    }

    private function parsePrecio($precio)
    {
        // Elimina el símbolo "$" y espacios en blanco al principio y al final
        $precio = trim(str_replace('$', '', $precio));

        // Elimina los valores ".00" o ",00" al final del precio
        $precio = preg_replace('/(\.00|,00)$/', '', $precio);

        return $precio;
    }


}
