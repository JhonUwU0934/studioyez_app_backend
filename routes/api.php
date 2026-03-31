<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\GastoController;
use App\Http\Controllers\Api\MontoController;
use App\Http\Controllers\Api\VentasController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\IngresoDeMercanciaController;
use App\Http\Controllers\Api\DevolucionClienteAlmacenController;
use App\Http\Controllers\Api\DevolucionAlmacenFabricaController;
use App\Models\Monto;
use App\Models\Ventas;
use App\Models\Gastos;
use Illuminate\Support\Facades\DB;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('/register', 'App\Http\Controllers\Api\auth\AuthController@register');
Route::post('/login', 'App\Http\Controllers\Api\auth\AuthController@login');
Route::post('/password/email', 'App\Http\Controllers\Api\auth\ResetPasswordController@sendResetLinkEmail');
Route::post('/password/reset', 'App\Http\Controllers\Api\auth\ResetPasswordController@reset')->name('password.reset');
Route::get('/have-access', 'App\Http\Controllers\Api\auth\AuthController@haveAccess');
Route::post('/password/reset/check/token', 'App\Http\Controllers\Api\auth\ResetPasswordController@checkResetPasswordToken');



Route::middleware(['jwt.auth'])->prefix('v1')->group(function () {
    // TUS RUTAS EXISTENTES (mantener todas)
    Route::post('/factura', 'App\Http\Controllers\Api\FacturaController@createInvoice');

    // Agregar al final del Route::middleware(['jwt.auth'])->prefix('v1')->group()
    Route::get('productos-diagnostico', [ProductoController::class, 'diagnosticar']);

    Route::apiResource('gastos', GastoController::class);
    Route::apiResource('monto', MontoController::class);
    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('inventario', InventarioController::class);
    Route::apiResource('ventas', VentasController::class);
    Route::apiResource('devolucionclientealmacen', DevolucionClienteAlmacenController::class);
    Route::apiResource('devolucionalmacenfabrica', DevolucionAlmacenFabricaController::class);
    Route::apiResource('ingresodemercancia', IngresoDeMercanciaController::class);


    Route::post('ventas/{id}/pagar-credito', [VentasController::class, 'pagarCredito']);
    
    // Obtener lista de créditos pendientes
    Route::get('creditos-pendientes', [VentasController::class, 'creditosPendientes']);
    
    // Extender fecha de promesa de pago
    Route::post('ventas/{id}/extender-credito', [VentasController::class, 'extenderCredito']);
    
    // Ver información detallada de una venta (incluyendo crédito)
    Route::get('ventas/{id}/detalle-completo', [VentasController::class, 'verDetalleCompleto']);
    
    // Obtener resumen de créditos por vendedor
    Route::get('vendedores/{vendedor}/creditos', [VentasController::class, 'creditosPorVendedor']);
    
    // Obtener estadísticas de créditos
    Route::get('creditos/estadisticas', [VentasController::class, 'estadisticasCreditos']);
    // NUEVAS RUTAS PARA LAS NUEVAS FUNCIONALIDADES
    
    // Rutas para gestión de colores
    Route::apiResource('colores', 'App\Http\Controllers\Api\ColorController');
    
    // Rutas para gestión de tallas
    Route::apiResource('tallas', 'App\Http\Controllers\Api\TallaController');
    
    // Rutas para gestión de imágenes de productos
    Route::get('productos/{producto}/imagenes', 'App\Http\Controllers\Api\ProductoImagenController@index');
    Route::post('productos/imagenes', 'App\Http\Controllers\Api\ProductoImagenController@store');
    Route::get('productos/imagenes/{imagen}', 'App\Http\Controllers\Api\ProductoImagenController@show');
    Route::put('productos/imagenes/{imagen}', 'App\Http\Controllers\Api\ProductoImagenController@update');
    Route::delete('productos/imagenes/{imagen}', 'App\Http\Controllers\Api\ProductoImagenController@destroy');
    
    // Rutas para gestión de variantes de productos
    Route::get('productos/{producto}/variantes', 'App\Http\Controllers\Api\ProductoVarianteController@index');
    Route::post('productos/variantes', 'App\Http\Controllers\Api\ProductoVarianteController@store');
    Route::get('productos/variantes/{variante}', 'App\Http\Controllers\Api\ProductoVarianteController@show');
    Route::put('productos/variantes/{variante}', 'App\Http\Controllers\Api\ProductoVarianteController@update');
    Route::delete('productos/variantes/{variante}', 'App\Http\Controllers\Api\ProductoVarianteController@destroy');
    
    // Rutas específicas para agregar imágenes y variantes a productos existentes
    Route::post('productos/{producto}/agregar-imagen', 'App\Http\Controllers\Api\ProductoController@agregarImagen');
    Route::post('productos/{producto}/agregar-variante', 'App\Http\Controllers\Api\ProductoController@agregarVariante');

    // TUS RUTAS EXISTENTES (mantener todas las demás)
    Route::get('users', 'App\Http\Controllers\Controller@getUsers');


    Route::get('cierres', function (\Illuminate\Http\Request $request) {
        $periodo = $request->input('periodo', 'dia');
        $limite = (int) $request->input('limite', 90);

        if ($periodo === 'semana') {
            $groupBy = DB::raw('YEAR(fecha) as anio, WEEK(fecha, 1) as semana');
            $selectFecha = DB::raw("DATE_FORMAT(MIN(fecha), '%Y-%m-%d') as fecha_inicio, DATE_FORMAT(MAX(fecha), '%Y-%m-%d') as fecha_fin");
            $groupClause = [DB::raw('YEAR(fecha)'), DB::raw('WEEK(fecha, 1)')];
            $orderBy = [DB::raw('YEAR(fecha) DESC'), DB::raw('WEEK(fecha, 1) DESC')];
        } elseif ($periodo === 'mes') {
            $groupBy = DB::raw('YEAR(fecha) as anio, MONTH(fecha) as mes');
            $selectFecha = DB::raw("DATE_FORMAT(MIN(fecha), '%Y-%m-%d') as fecha_inicio, DATE_FORMAT(MAX(fecha), '%Y-%m-%d') as fecha_fin");
            $groupClause = [DB::raw('YEAR(fecha)'), DB::raw('MONTH(fecha)')];
            $orderBy = [DB::raw('YEAR(fecha) DESC'), DB::raw('MONTH(fecha) DESC')];
        } else {
            $groupBy = DB::raw('fecha');
            $selectFecha = DB::raw("fecha as fecha_inicio, fecha as fecha_fin");
            $groupClause = ['fecha'];
            $orderBy = [DB::raw('fecha DESC')];
        }

        $ventas = DB::table('ventas')
            ->select(
                $groupBy,
                $selectFecha,
                DB::raw('COUNT(*) as cantidad_ventas'),
                DB::raw('COALESCE(SUM(precio_venta), 0) as total_ventas'),
                DB::raw('COALESCE(SUM(valor_efectivo), 0) as total_efectivo'),
                DB::raw('COALESCE(SUM(valor_transferencia), 0) as total_transferencia'),
                DB::raw('COALESCE(SUM(valor_credito), 0) as total_credito')
            )
            ->groupBy($groupClause)
            ->orderByRaw(implode(', ', array_map(fn($o) => $o->getValue(DB::connection()->getQueryGrammar()), $orderBy)))
            ->limit($limite)
            ->get();

        $gastos = DB::table('gastos')
            ->select(
                $periodo === 'dia' ? DB::raw('fecha') : ($periodo === 'semana' ? DB::raw('YEAR(fecha) as anio, WEEK(fecha, 1) as semana') : DB::raw('YEAR(fecha) as anio, MONTH(fecha) as mes')),
                DB::raw('COALESCE(SUM(monto), 0) as total_gastos'),
                DB::raw('COUNT(*) as cantidad_gastos')
            )
            ->groupBy($groupClause)
            ->get()
            ->keyBy(function ($item) use ($periodo) {
                if ($periodo === 'dia') return $item->fecha;
                if ($periodo === 'semana') return $item->anio . '-W' . $item->semana;
                return $item->anio . '-' . $item->mes;
            });

        $cierres = $ventas->map(function ($v) use ($periodo, $gastos) {
            if ($periodo === 'dia') {
                $key = $v->fecha_inicio;
            } elseif ($periodo === 'semana') {
                $key = $v->anio . '-W' . $v->semana;
            } else {
                $key = $v->anio . '-' . $v->mes;
            }

            $gasto = $gastos->get($key);
            $totalGastos = $gasto ? (float) $gasto->total_gastos : 0;
            $cantidadGastos = $gasto ? (int) $gasto->cantidad_gastos : 0;

            return [
                'fecha_inicio' => $v->fecha_inicio,
                'fecha_fin' => $v->fecha_fin,
                'cantidad_ventas' => (int) $v->cantidad_ventas,
                'total_ventas' => (float) $v->total_ventas,
                'total_efectivo' => (float) $v->total_efectivo,
                'total_transferencia' => (float) $v->total_transferencia,
                'total_credito' => (float) $v->total_credito,
                'cantidad_gastos' => $cantidadGastos,
                'total_gastos' => $totalGastos,
                'balance' => (float) $v->total_ventas - $totalGastos,
            ];
        });

        return response()->json(['data' => $cierres->values()]);
    });

    Route::get('balance',function () {
        $cantidadVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('cantidad');
        $totalVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('precio_venta');
        $totalEfectivo = Ventas::whereDate('fecha', now()->toDateString())->sum('valor_efectivo');
        $totalTransferencia = Ventas::whereDate('fecha', now()->toDateString())->sum('valor_transferencia');
        $totalCredito = Ventas::whereDate('fecha', now()->toDateString())->sum('valor_credito');
        $totalGastos = Gastos::whereDate('fecha', now()->toDateString())->sum('monto');

        $monto = Monto::whereDate('created_at', now()->toDateString())->first();
        $montoDiario = $monto ? $monto->monto : 0;
        $montoID = $monto ? $monto->id : null;

        $balanceDiario = ($totalVentas + $montoDiario) - $totalGastos;
        $efectivoEnCaja = ($montoDiario + $totalEfectivo) - $totalGastos;

        return response()->json([
            'success' => true,
            'action' => 'Balance',
            'message' => 'Balance diario consultado',
            'code' => 200,
            'monto_id' => $montoID,
            'cantidad_ventas' => $cantidadVentas,
            'total_ventas' => $totalVentas,
            'total_efectivo' => $totalEfectivo,
            'total_transferencia' => $totalTransferencia,
            'total_credito' => $totalCredito,
            'efectivo_en_caja' => $efectivoEnCaja,
            'total_gastos' => $totalGastos,
            'balance_diario' => $balanceDiario,
            'monto_diario' => $montoDiario,
            'error' => null
        ], 200);
    });

});



