<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{

    private $rules = [
        'NIT'           =>      'required|unique:customers',
        'firstName'     =>      'required',
        'lastName'      =>      'required',
        'address'       =>      'required',
        'telephone'     =>      'required|unique:customers|integer|digits:8'
    ];

    private $messages = [
        'NIT.required'         =>  'El campo NIT es obligatorio',
        'NIT.unique'           =>  'El campo NIT ya existe en nuestra base de datos',
        'firstName.required'   =>  'El campo Nombres es obligatorio',
        'lastName.required'    =>  'El campo apellidos es obligatorio',
        'address.required'     =>  'El campo direccion es obligatorio',
        'telephone.required'   =>  'El campo telefono es obligatorio',
        'telephone.unique'     =>  'El campo telefono ya existe en nuestra base de datos',
        'telephone.integer'    =>  'El campo telefono debe ser un numerico',
        'telephone.digits'     =>  'El campo telefono debe contener 8 digitos'
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
        $customers = Customer::paginate(15);

        return response()->json([
            'ok' => true,
            'data' => $customers
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

        $customer = Customer::create($input);

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado satisfactoriamente',
            'data' => $customer
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        if (is_null($customer)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $customer
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Customer $customer)
    {
        $input = $request->all();

        // Personalizar reglas de validacion al modificar un cliente
        $validator = Validator::make($input, [
            'NIT'           =>      'unique:customers,NIT,' . $customer->id,
            'telephone'     =>      'integer|digits:8|unique:customers,telephone,' . $customer->id
        ], $this->messages);


        if ($validator->fails()) {

            $errors = $validator->errors();

            return response()->json([
                'ok' => false,
                'message' => 'Error en validación de formulario',
                'errors' => $errors
            ], 400);


        }

        if ($request->has('NIT')) {
            $customer->NIT = $input['NIT'];
        }

        if ($request->has('firstName')) {
            $customer->firstName = $input['firstName']; 
        }

        if ($request->has('lastName')) {
            $customer->lastName = $input['lastName'];
        }

        if ($request->has('address')) {
            $customer->address = $input['address'];
        }

        if ($request->has('telephone')) {
            $customer->telephone = $input['telephone'];
        }

        if ($customer->isClean()) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Se debe especificar al menos un valor diferente para actualizar',
                    'code' => '422'
                ]
            ], 422);
        }

        $customer->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $customer
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        $customer->status = 'Inactivo';
        $customer->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $customer
        ], 200);

    }
}
