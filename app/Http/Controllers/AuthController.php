<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator; 

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct()  {     

        $this->middleware('jwt', ['except' => ['login']]);   
          
    }
    
    public function login() {

        $credentials = request(['email', 'password']); 

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()) {

            return response()->json([
                'data' => [
                'ok' => false,
                'message' => 'Wrong validation',
                'error' => $validator->errors(),
                'code' => 422
                ]
            ], 422);
        }

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Unauthorized',
                    'code' => 401
                    ]
                ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function showMe()
    {
        return response()->json(auth('api')->user());
    }

    public function payload()
    {
        return response()->json(auth('api')->payload());
    }

    public function logout()
    {
        auth('api')->logout();
            return response()->json([
                'data' => [
                    'ok' => true,
                    'message' => 'Successfully logged out',
                    'code' => 200
                ]
            ], 200);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'data' => [
                'ok' => true,
                'message' => 'Successfully logged',
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => auth('api')->user(),
                'code' => 200
            ]
        ], 200);
    }


    
}
