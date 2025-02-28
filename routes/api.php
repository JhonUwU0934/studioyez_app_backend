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
    Route::post('/factura', 'App\Http\Controllers\Api\FacturaController@createInvoice');

    Route::apiResource('gastos', GastoController::class);
    Route::apiResource('monto', MontoController::class);
    Route::apiResource('productos', ProductoController::class);
    Route::apiResource('inventario', InventarioController::class);
    Route::apiResource('ventas', VentasController::class);
    Route::apiResource('devolucionclientealmacen', DevolucionClienteAlmacenController::class);
    Route::apiResource('devolucionalmacenfabrica', DevolucionAlmacenFabricaController::class);
    Route::apiResource('ingresodemercancia', IngresoDeMercanciaController::class);



    Route::get('users', 'App\Http\Controllers\Controller@getUsers');


    Route::get('balance',function () {
        // Obtener la misma información que se muestra en el login
        $cantidadVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('cantidad');

        // Obtener el total de ventas del día
        $totalVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('precio_venta');

        // Obtener el total de gastos del día
        $totalGastos = Gastos::whereDate('fecha', now()->toDateString())->sum('monto');

        // Obtener el registro de monto del día actual
        $monto = Monto::whereDate('created_at', now()->toDateString())->first();
        $montoID = Monto::whereDate('created_at', now()->toDateString())->first();

        $montoDiario = $monto ? $monto->monto : 0; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0
        $montoID = $monto ? $monto->id : null; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0


        // Calcular el balance diario
        $balanceDiario = ($totalVentas + $montoDiario) - $totalGastos;

        return response()->json([
            'success' => true,
            'action' => 'Balance',
            'message' => 'Balance diario consultado',
            'code' => 200,
            'monto_id' => $montoID,
            'cantidad_ventas' => $cantidadVentas,
            'total_ventas' => $totalVentas,
            'total_gastos' => $totalGastos,
            'balance_diario' => $balanceDiario,
            'monto_diario' => $montoDiario,
            'error' => null
        ], 200);
    });

});



