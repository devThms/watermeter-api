<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZoneController extends Controller
{

    private $rules = [
        'description'   =>      'required',
        'dateMax'       =>      'required|integer|digits:2',
    ];

    private $messages = [
        'description.required'  =>  'El campo descripcion es obligatorio',
        'dateMax.required'      =>  'El campo FechaMaxima es obligatorio',
        'dateMax.integer'       =>  'El campo FechaMaxima debe ser un dato numerico',
        'dateMax.digits'        =>  'El campo FechaMaxima debe contener 2 digitos numericos'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $zones = Zone::all();

        return response()->json([
            'ok' => true,
            'data' => $zones
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

        $zone = Zone::create($input);

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado satisfactoriamente',
            'data' => $zone
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Zone  $zone
     * @return \Illuminate\Http\Response
     */
    public function show(Zone $zone)
    {
        if (is_null($zone)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $zone
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Zone  $zone
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Zone $zone)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'dateMax'       =>      'integer|digits:2'
        ], $this->messages);

        if ($validator->fails())
        {
            $errors = $validator->errors();

            return response()->json([
                'ok' => false,
                'message' => 'Error en validación de formulario',
                'errors' => $errors
            ], 400);

        }

        if ($request->has('description')) {
            $zone->description = $input['description'];
        }

        if ($request->has('dateMax')) {
            $zone->dateMax = $input['dateMax'];
        }

        if ($zone->isClean()) {
            return response()->json([
                'error' => 'Se debe especificar al menos un valor diferente para actualizar',
                'code' => '422'
            ], 422);
        }

        $zone->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $zone
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Zone  $zone
     * @return \Illuminate\Http\Response
     */
    public function destroy(Zone $zone)
    {
        $zone->delete();

        $zone->status = 'Inactivo';
        $zone->save();
        
        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $zone
        ], 200);
    }
}
