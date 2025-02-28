<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use App\Models\User;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function getUsers(){
        $user = User::get();

        return response()->json([
            'success' => true,
            'data' => $user,
            'code' => 200
        ],200);
    }

    public function apiInsertUser(Request $request){

        try {

            User::factory()->create([
                'name' => $request->input('user'),
                'email' => $request->input('email'),
           ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th,
                'code' => 500
            ],500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usted ha insertado la información para el cliente '.$request->input('user'),
            'code' => 201
        ],201);

    }
}
