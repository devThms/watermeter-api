<?php

namespace App\Http\Controllers;

use App\Models\CashReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashReceiptController extends Controller
{
    private $rules = [
        'order_id'          =>      'required',
        'user_id'           =>      'required',
        'ammount'           =>      'required'
    ];

    private $messages = [
        'order_id.required'         =>  'El campo orden es obligatorio',
        'user_id.required'          =>  'El campo usuario es obligatorio',
        'ammount.required'          =>  'El campo cantidad es obligatorio'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cashReceipts = CashReceipt::all();

        return response()->json([
            'ok' => true,
            'data' => $cashReceipts
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

        $cashReceipt = new CashReceipt();

        $cashReceipt->order_id = $input['order_id'];
        $cashReceipt->user_id = $input['user_id'];
        $cashReceipt->ammount = $input['ammount'];

        $cashReceipt->description = 'Pago servicio de agua correspondiente al mes de ' . $cashReceipt->order->month . ' orden No. 0000' . $input['order_id'];

        $cashReceipt->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado satisfactoriamente',
            'data' => $cashReceipt
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function show(CashReceipt $cashReceipt)
    {
        if (is_null($cashReceipt)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $cashReceipt
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CashReceipt $cashReceipt)
    {
        $input = $request->all();

        if ($request->has('ammount')) {
            $cashReceipt->ammount = $input['ammount'];
        }

        if ($request->has('description')) {
            $cashReceipt->description = $input['description'];
        }

        if ($cashReceipt->isClean()) {
            return response()->json([
                'error' => 'Se debe especificar al menos un valor diferente para actualizar',
                'code' => '422'
            ], 422);
        } 

        $cashReceipt->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $cashReceipt
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CashReceipt  $cashReceipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(CashReceipt $cashReceipt)
    {
        $cashReceipt->delete();

        $cashReceipt->status = 'Anulado';
        $cashReceipt->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $cashReceipt
        ], 200);
    }
}
