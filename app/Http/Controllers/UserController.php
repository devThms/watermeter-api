<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    private $rules = [
        'name'      =>      'required',
        'email'     =>      'required|email|unique:users',
        'password'  =>      'required',
        'role_id'   =>      'required'
    ];

    private $messages = [
        'name.required'         =>  'El campo nombre es obligatorio',
        'email.required'        =>  'El campo email es obligatorio',
        'email.email'           =>  'El correo electronico debe ser una direccion de correo valida',
        'password.required'     =>  'El campo password es obligatorio',
        'role_id.required'      =>  'El campo role es obligatorio'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();

        return response()->json([
            'ok' => true,
            'data' => $users
        ], 200);
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
                'ok' => false,
                'message' => 'Error en validación de formulario',
                'errors' => $errors
            ], 400);

        }

        // Encriptar la contraseña
        $input['password'] = bcrypt($input['password']);

        $user = User::create($input);

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
                ['error' => 'Recurso no encontrado']
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
                'ok' => false,
                'message' => 'Error en validación de formulario',
                'errors' => $errors
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
                'error' => 'Se debe especificar al menos un valor diferente para actualizar',
                'code' => '422'
            ], 422);
        }

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
                ['error' => 'Recurso no encontrado']
            ], 404);

        }
        
        $user->delete();

        $user->status = 'Inactivo';
        $user->save();
        
        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $user
        ], 200);
    }
}
