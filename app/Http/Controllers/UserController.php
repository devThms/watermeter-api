<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    private $rules = [
        'name'      =>      'required',
        'email'     =>      'required|email|unique:users',
        'password'  =>      'required'
    ];

    private $messages = [
        'name.required'         =>  'El campo nombre es obligatorio',
        'email.required'        =>  'El campo email es obligatorio',
        'email.email'           =>  'El correo electronico debe ser una direccion de correo valida',
        'password.required'     =>  'El campo password es obligatorio'
    ];

    public function __construct()
    {
        // $this->middleware('jwt', ['except' => ['store']]);   
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('role')->paginate(15);

        return response()->json([
            'ok' => true,
            'data' => $users
        ], 200);
    }


    public function login(Request $request) {

        $input = $request->all();

        $user = User::with('role')->where('email', '=', $input['email'])
                    ->first();

        if ($user === null) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'error' => 'credenciales incorrectas',
                    'code' => '200'
                ]
            ], 200);
        } else {
            if (Hash::check($input['password'], $user->password)){
                return response()->json([
                    'ok' => true,
                    'data' => $user
                ], 200);
            } else {
                return response()->json([
                    'data' => [
                        'ok' => false,
                        'error' => 'credenciales incorrectas',
                        'code' => '200'
                    ]
                ], 200);
            }
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails())
        {
            $errors = $validator->errors();

            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Error en validación de formulario',
                    'errors' => $errors,
                    'code' => '400'
                ]
            ], 400);

        }

        $role = Role::where('description', 'Operador')->first();
        
        $user = new User();

        $user->name = $input['name'];
        $user->email = $input['email'];
        $user->password = bcrypt($input['password']);
        $user->role_id = $role->id;

        $user->role;
        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Regsitro creado satisfactoriamente',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        if (is_null($user)) {

            return response()->json([
                'data' => [
                    'ok' => false,
                    'error' => 'Recurso no encontrado',
                    'code' => '404'
                ]
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $user
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $input = $request->all();

        // Personalizar reglas de validacion al modificar un usuario
        $validator = Validator::make($input, [
            'email'     =>      'email|unique:users,email,' . $user->id,
        ], $this->messages);


        if ($validator->fails()) {

            $errors = $validator->errors();

            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Error en validación de formulario',
                    'code' => '400'
                ]
            ], 400);


        }


        if ($request->has('name')) {
            $user->name = $input['name'];
        }

        if ($request->has('password')) {
            $user->password = bcrypt($input['password']);
        }

        if ($request->has('email') && $user->email != $input['email']) {
            $user->email = $input['email'];
        }

        if ($request->has('role_id')) {
            $user->role_id = $input['role_id'];
        }

        if ($user->isClean()) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Se debe especificar al menos un valor diferente para actualizar',
                    'code' => '422'
                ]
            ], 422);
        }

        $user->role;
        $user->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $user
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {

        if (is_null($user)) {

            return response()->json([
                'data' => [
                    'ok' => false,
                    'error' => 'Recurso no encontrado',
                    'code' => '404'
                ]
            ], 404);

        }
        
        $user->delete();
        
        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $user
        ], 200);
    }
}
