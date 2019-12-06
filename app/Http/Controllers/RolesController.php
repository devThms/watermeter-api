<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{

    private $rules = [
        'description'    =>  'required'
    ];

    private $messages = [
        'description.required'       => 'El campo descripcion es obligatorio'
    ];


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();

        return response()->json([
            'ok' => true,
            'data' => $roles
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
        
        $role = Role::create($input);

        return response()->json([
            'ok' => true,
            'message' => 'Regsitro creado satisfactoriamente',
            'data' => $role
        ], 201);

        // return response()->json( new RoleResource($role), 201);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        if (is_null($role)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $role
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $input = $request->all();

        $validator = Validator::make($input, $this->rules, $this->messages);

        if ($validator->fails()) {

            return response()->json([
                $validator->errors()
            ], 406);

        }

        $role->description = $input['description'];
        $role->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $role
        ]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $role->delete();

        $role->status = 'Inactivo';
        $role->save();
        
        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $role
        ], 200);
    }
}
