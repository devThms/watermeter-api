<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{

    private $rules = [
        'cash_receipt_id'          =>      'required',
        'user_id'                  =>      'required',
        'ammount'                  =>      'required'
    ];

    private $messages = [
        'cash_receipt_id.required'         =>  'El campo recibo de caja es obligatorio',
        'user_id.required'                 =>  'El campo usuario es obligatorio',
        'ammount.required'                 =>  'El campo cantidad es obligatorio'
    ];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $invoices = Invoice::all();

        return response()->json([
            'ok' => true,
            'data' => $invoices
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

        $invoice = new Invoice();

        $invoice->cash_receipt_id = $input['cash_receipt_id'];
        $invoice->user_id = $input['user_id'];
        $invoice->description = 'Pago por servicio de agua correspondiente al mes ' . $invoice->cashReceipt->order->month . ' recibo de caja No. 0000' . $input['cash_receipt_id'];
        $invoice->ammount = $input['ammount'];

        $invoice->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado satisfactoriamente',
            'data' => $invoice
        ], 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show(Invoice $invoice)
    {
        if (is_null($invoice)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $invoice
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Invoice $invoice)
    {
        $input = $request->all();

        if ($request->has('ammount')) {
            $invoice->ammount = $input['ammount'];
        }

        if ($request->has('description')) {
            $invoice->description = $input['description'];
        }

        if ($invoice->isClean()) {
            return response()->json([
                'error' => 'Se debe especificar al menos un valor diferente para actualizar',
                'code' => '422'
            ], 422);
        } 

        $invoice->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $invoice
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        $invoice->status = 'Anulado';
        $invoice->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $invoice
        ], 200);
    }
}
