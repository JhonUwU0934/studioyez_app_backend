<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\User;
use App\Models\Ventas;
use App\Models\Gastos;
use App\Models\Monto;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'action' => 'Acceso de inicio de sesión',
                    'message' => 'Credenciales no válidas',
                    'code' => 401,
                    'user' => null,
                    'token' => null,
                    'error' => null
                ], 401);
            }


    
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'action' => 'Acceso de inicio de sesión',
                'message' => 'No se pudo crear el token',
                'code' => 500,
                'user' => null,
                'token' => null,
                'error' => $e->getMessage()
            ], 500);
        }
        $user = Auth::user();

        $cantidadVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('cantidad');

        // Obtener el total de ventas del día
        $totalVentas = Ventas::whereDate('fecha', now()->toDateString())->sum('precio_venta');

        // Obtener el total de gastos del día
        $totalGastos = Gastos::whereDate('fecha', now()->toDateString())->sum('monto');

        // Obtener el registro de monto del día actual
        $monto = Monto::whereDate('created_at', now()->toDateString())->first();

        $montoID = Monto::whereDate('created_at', now()->toDateString())->first();
        $montoDiario = $monto ? $monto->monto : 0;
        $montoID = $monto ? $monto->id : null; // Si $monto no es nulo, obtiene el monto, de lo contrario, asigna 0

        // Calcular el balance diario
        $balanceDiario = ($totalVentas + $montoDiario) - $totalGastos;

        return response()->json([
            'success' => true,
            'action' => 'Acceso de inicio de sesión',
            'message' => 'Usuario conectado',
            'code' => 201,
            'user' => $user,
            'token' => $token,
            'monto_id' => $montoID,
            'cantidad_ventas' => $cantidadVentas,
            'total_ventas' => $totalVentas,
            'total_gastos' => $totalGastos,
            'balance_diario' => $balanceDiario,
            'monto_diario' => $montoDiario,
            'error' => null
        ], 201);
    }


    public function register(Request $request)
    {
        try {
            // Valida los datos del usuario
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users|max:255',
                'password' => 'required|confirmed|min:8',
                'password_confirmation' => 'required|min:8',
                ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'action' => 'proceso de registro',
                'message' => 'Error de validación, por favor vuelve a intentarlo.',
                'code' => 422,
                'user' => null,
                'token' => null,
                'error' => $e
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => 8
        ]);

        Auth::login($user);

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'action' => 'proceso de registro',
                'message' => 'El token no se ha podido crear, por favor vuelve a intentarlo',
                'code' => 500,
                'user' => null,
                'token' => null,
                'error' => $e
            ], 500);
        }

        return response()->json([
            'success' => true,
            'action' => 'proceso de registro',
            'message' => 'El registro ha sido exitoso',
            'code' => 201,
            'user' => $user,
            'token' => $token,
            'error' => null
        ], 201);
    }


    public function haveAccess()
    {
        try {
            $payload = JWTAuth::parseToken()->payload();
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'action' => 'have access',
                'message' => 'token expired',
                'code' => 401,
                'user' => null,
                "payload" => null,
                'error' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'action' => 'have access',
                'message' => 'the user does not have access to the system',
                'code' => 401,
                'user' => null,
                "payload" => null,
                'error' => $e->getMessage()
            ], 401);
        }

        return response()->json([
            'success' => true,
            'action' => 'have access',
            'message' => 'the user has access to the system',
            'code' => 201,
            'user' => $user,
            "payload" => $expirationTime = $payload['exp'] - $payload['iat'],
            'error' => null
        ], 201);
    }
}

