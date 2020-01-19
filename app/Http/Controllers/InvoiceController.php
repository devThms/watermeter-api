<?php

namespace App\Http\Controllers;

use App\Models\CashReceipt;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function __construct()
    {
        // $this->middleware('jwt');
    }

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

    public function invoice_log($meter)
    {

        $invoices = DB::table('invoices as i')
                        ->join('cash_receipts as cr', 'i.cash_receipt_id', '=', 'cr.id')
                        ->join('orders as o', 'cr.order_id', '=', 'o.id')
                        ->where('o.meter_id', '=', $meter)
                        ->where('i.status', '<>', 'Emitido')
                        ->select('i.*', 'o.month')
                        ->paginate(10);

        return response()->json([
            'ok' => true,
            'data' => $invoices
        ], 200);

    }

    public function pending_payment($meter)
    {

        $invoices = DB::table('invoices as i')
                        ->join('cash_receipts as cr', 'i.cash_receipt_id', '=', 'cr.id')
                        ->join('orders as o', 'cr.order_id', '=', 'o.id')
                        ->where('o.meter_id', '=', $meter)
                        ->where('i.status', '=', 'Emitido')
                        ->orderBy('i.id', 'asc')
                        ->select('i.*', 'o.month')
                        ->paginate(10);

        return response()->json([
            'ok' => true,
            'data' => $invoices
        ], 200);

    }

    public function report($user, $from, $to) {


        $invoices = DB::select('select * from invoices where user_id = :user and status = :status and substring(created_at, 1, 10) between :from and :to', ["user" => $user, "status" => "Pagado", "from" => $from, "to" => $to]);

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
        $invoice->ammount = $input['ammount'];
        $invoice->description = 'Pago por servicio de agua correspondiente al mes ' . $invoice->cashReceipt->order->month . ' recibo de caja No. 0000' . $input['cash_receipt_id'];

        $invoice->save();

        $cash_receipt = CashReceipt::find($invoice->cash_receipt_id);

        $order = Order::find($cash_receipt->order_id);
        $order->status = 'Facturado';

        $order->save();

        // validar las facturas pendientes de pago
        $pendientePago = DB::select('select count(*) as cantidad ' .
                                    'from invoices as i ' .
                                    'inner join cash_receipts as cr on i.cash_receipt_id = cr.id ' .
                                    'inner join orders as o on cr.order_id = o.id ' .
                                    'where i.status = :status and o.meter_id = :meter', ["status" => "Emitido", "meter" => $order->meter_id]);
        
        if ((int)$pendientePago[0]->cantidad > 3) {

            $meter = Meter::find($order->meter_id);
            $meter->status = 'Corte Servicio';

            $meter->save();

            return response()->json([
                'ok' => true,
                'message' => 'Registro creado satisfactoriamente',
                'data' => $invoice,
                'warnings' => [
                    'type' => 'info',
                    'messages' => [
                        '1' => 'El medidor serie No. ' . $invoice->cashReceipt->order->meter->serialNumber . ' fue declarado en corte de servicio por contar con 4 facturas pendientes de pago'
                    ]
                ]
            ], 201);

        }

        
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
                'data' => [
                    'ok' => false,
                    'message' => 'Se debe especificar al menos un valor diferente para actualizar',
                    'code' => '422'
                ]
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

    // Crear ruta para proceso de reversion de contabilizacion de factura y orden pendiente de pago nuevamente
    public function cancel(Invoice $invoice) {

        if ($invoice->status === 'Anulado') {

            return response()->json([
                'ok' => false,
                'message' => 'La factura ya se encuentra anulada'
            ], 200);

        }

        $invoice->status = 'Anulado';
        $invoice->save();

        $cash_receipt = CashReceipt::find($invoice->cash_receipt_id);
        $cash_receipt->status = 'Anulado';
        $cash_receipt->save();

        $order = Order::find($cash_receipt->order_id);
        $order->status = 'PendientePago';

        $order->save();

        return response()->json([
            'ok' => true,
            'message' => 'Proceso anulación realizado satisfactoriamente',
            'factura' => $invoice
        ], 200);

    }

    public function payment(Invoice $invoice) 
    {

        $invoice->status = 'Pagado';
        $invoice->save();

        $cash_receipt = CashReceipt::find($invoice->cash_receipt_id);
        $cash_receipt->status = 'Pagado';
        $cash_receipt->save();

        $order = Order::find($cash_receipt->order_id);
        $order->status = 'Pagado';
        $order->save();

        // validar las facturas pendientes de pago
        $pendientePago = DB::select('select count(*) as cantidad ' .
                                    'from invoices as i ' .
                                    'inner join cash_receipts as cr on i.cash_receipt_id = cr.id ' .
                                    'inner join orders as o on cr.order_id = o.id ' .
                                    'where i.status = :status and o.meter_id = :meter', ["status" => "Emitido", "meter" => $order->meter_id]);
        
        $meter = Meter::find($order->meter_id);

        if ((int)$pendientePago[0]->cantidad == 3) {

            $meter->status = 'Suspendido Temporalmente';
            $meter->save();

        }

        if ((int)$pendientePago[0]->cantidad < 3) {

            $meter->status = 'Activo';
            $meter->save();

        }

        return response()->json([
            'ok' => true,
            'message' => 'Factura pagada satisfactoriamente',
            'factura' => $invoice,
            'recibo_caja' => $cash_receipt,
            'orden' => $order
        ], 200);

    }
}
