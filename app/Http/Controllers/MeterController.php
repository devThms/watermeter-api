<?php

namespace App\Http\Controllers;

use App\Models\Meter;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MeterController extends Controller
{

    private $rules = [
        'customer_id'       =>      'required',
        'serialNumber'      =>      'required',
        'address'           =>      'required',
        'zone_id'           =>      'required'
    ];

    private $messages = [
        'customer_id.required'      =>  'El campo cliente es obligatorio',
        'serialNumber.required'     =>  'El campo numero de serie es obligatorio',
        'address.required'          =>  'El campo direccion es obligatorio',
        'zone_id.required'          =>  'El campo zona es obligatorio'
    ];

    public function __construct()
    {
        $this->middleware('jwt');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meters = Meter::all();

        return response()->json([
            'ok' => true,
            'data' => $meters
        ], 200);
    }


    // Obtener medidores por zona
    public function meter_zone($zone)
    {
        $meters = Meter::with('customer')->where('zone_id', '=', $zone)->paginate(15);

        return response()->json([
            'ok' => true,
            'data' => $meters
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
                'data' => [
                    'ok' => false,
                    'message' => 'Error en validación de formulario',
                    'error' => $errors,
                    'code' => '400'
                ]
            ], 400);

        }

        $meter = Meter::create($input);

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado satisfactoriamente',
            'data' => $meter
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Meter  $meter
     * @return \Illuminate\Http\Response
     */
    public function show(Meter $meter)
    {
        if (is_null($meter)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $meter
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Meter  $meter
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meter $meter)
    {
        $input = $request->all();

        if ($request->has('customer_id')) {
            $meter->customer_id = $input['customer_id'];
        }

        if ($request->has('serialNumber')) {
            $meter->serialNumber = $input['serialNumber'];
        }

        if ($request->has('address')) {
            $meter->address = $input['address'];
        }

        if ($request->has('zone_id')) {
            $meter->zone_id = $input['zone_id'];
        }

        if ($meter->isClean()) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Se debe especificar al menos un valor diferente para actualizar',
                    'code' => '422'
                ]
            ], 422);
        }

        $meter->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $meter
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Meter  $meter
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meter $meter)
    {
        $meter->delete();

        // cambiar estado al momento de eliminarlo
        $meter->status = 'Baja Definitiva';
        $meter->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $meter
        ], 200);
    
    }
}
