<?php

namespace App\Http\Controllers;

use App\Models\CustomerBlacklist;
use App\Models\Meter;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    private $rules = [
        'meter_id'          =>      'required',
        'user_id'           =>      'required',
        'finalMeasure'      =>      'required|integer'
    ];

    private $messages = [
        'meter_id.required'         =>  'El campo medidor es obligatorio',
        'user_id.required'          =>  'El campo usuario es obligatorio',
        'finalMeasure.required'     =>  'El campo medicion final es obligatorio',
        'finalMeasure.integer'      =>  'El campo medicion final debe ser numerico'
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
        $orders = Order::all();

        return response()->json([
            'ok' => true,
            'data' => $orders
        ], 200);
    }


    // Metodo para obtener las ordenes pendientes de pago (tesoreria)
    public function pending_payment($meter) 
    {

        $orders = Order::where('meter_id', '=', $meter)
                        ->where('status', '=', 'PendientePago')
                        ->orderBy('month', 'asc')
                        ->paginate(10);

        return response()->json([
            'ok' => true,
            'data' => $orders
        ], 200);

    }

   
    // Metodo para obtener bitacora de ordenes por medidor
    public function order_log($meter) 
    {

        $orders = Order::where('meter_id', '=', $meter)
                        ->where('status', '<>', 'PendientePago')
                        ->orderBy('month', 'desc')
                        ->paginate(10);


        return response()->json([
            'ok' => true,
            'data' => $orders
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
                    'errors' => $errors
                ]
            ], 400);

        }
 
        $order = new Order();

        $order->meter_id = $input['meter_id'];
        $order->user_id = $input['user_id'];
        $order->finalMeasure = $input['finalMeasure'];

        // Manejando fechas con Carbon
        $date = Carbon::now();
        $order->year = $date->format('Y');
        $order->month = $date->format('m');
        $day = $date->format('d');

        // Validar si ya existe una orden registrada con el mes actual
        $mesActual = Order::where('month', '=', $order->month)->first();

        if ($mesActual) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Ya existe un registro en el mes, para el medidor selecionado'
                ]
            ]);
        }

        // Validar si la fecha de registro de la orden excede la configurada por la zona
        if ((int)$day > $order->meter->zone->dateMax) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'La fecha de registro excede a la fecha configurada para el medidor'
                ]
            ]);
        }


        
        // validar las facturas pendientes de pago
        $pendientePago = DB::select('select count(*) as cantidad ' .
                                    'from invoices as i ' .
                                    'inner join cash_receipts as cr on i.cash_receipt_id = cr.id ' .
                                    'inner join orders as o on cr.order_id = o.id ' .
                                    'where i.status = :status and o.meter_id = :meter', ["status" => "Emitido", "meter" => $order->meter_id]);

        if ((int)$pendientePago[0]->cantidad > 3) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'El medidor cuenta con 4 facturas pendientes de pago, no es posible generar una nueva orden'
                ]
               
            ]);
        }

        $order->initialMeasure = $order->previous_measure;

        // Validando la medida sea mayor que la final
        if ($order->finalMeasure < $order->initialMeasure) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'La lectura actual es menor a la ultima lectura, revise sus datos'
                ]
                
            ]);
        }


        $totalMeasure = $input['finalMeasure'] - $order->previous_measure;
        
        // Validando tabla de tarifas por consumo
        if ($totalMeasure <= 15 ) {
            $order->ammount = 30;
        }

        if ($totalMeasure > 15 && $totalMeasure <= 30) {
            $order->ammount = 50;
        } 

        if ($totalMeasure > 30 && $totalMeasure <= 75) {
            $order->ammount = 100;
        }

        if ($totalMeasure > 75 && $totalMeasure <= 100) {
            $order->ammount = 150;
        }

        if ($totalMeasure > 100) {
            $order->ammount = 250;
        }


        $order->save();


        if ((int)$pendientePago[0]->cantidad > 2) {

            $meter = Meter::find($order->meter_id);
            $meter->status = 'Suspendido Temporalmente';
            
            $meter->save();

            // Validar si el medidor registra alto consumo
            $altoConsumo = DB::select('select count(*) as cantidad from orders where (finalMeasure - initialMeasure) >= 100 and status = :status and meter_id = :meter', 
                                        ["status" => "Facturado", "meter" => $order->meter_id]);

            if ((int)$altoConsumo[0]->cantidad > 2) {

                $customerBlacklist = new CustomerBlacklist();
                $customerBlacklist->order_id = $order->id;

                $customerBlacklist->save();

                return response()->json([
                    'data' => [
                        'ok' => true,
                        'message' => 'Registro creado satisfactoriamente',
                        'data' => $order,
                        'warnings' => [
                            'type' => 'info',
                            'messages' => [
                                '1' => 'El medidor serie No. ' . $order->meter->serialNumber . ' fue asignado en la lista negra de clientes morosos',
                                '2' => 'El medidor serie No. ' . $order->meter->serialNumber . ' fue declarado como suspendido temporalmente, por tener facturas pendientes de pago'
                            ]
                        ]
                    ]
                   
                ], 201);

            } else {

                return response()->json([
                    'ok' => true,
                    'message' => 'Registro creado satisfactoriamente',
                    'data' => $order,
                    'warnings' => [
                        'type' => 'info',
                        'messages' => [
                            '1' => 'El medidor No. ' . $order->meter_id . ' fue asignado como suspendido temporalmente, por tener facturas pendientes de pago'
                        ]
                    ]
                ], 201);
                
            } 


        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro creado satisfactoriamente',
            'data' => $order
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {
        if (is_null($order)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $order
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        $input = $request->all();

        // Personalizar reglas de validacion al modificar una orden de medicion
        $validator = Validator::make($input, [
            'finalMeasure'          =>      'integer'
        ], $this->messages);

        if ($validator->fails()) {

            $errors = $validator->errors();

            return response()->json([
                'ok' => false,
                'message' => 'Error en validación de formulario',
                'errors' => $errors
            ], 400);

        }

        // Manejando fechas con Carbon
        $date = Carbon::now();
        $day = $date->format('d');

        // Validar si la fecha de registro de la orden excede la configurada por la zona
        if ((int)$day > $order->meter->zone->dateMax) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'La fecha de registro excede a la fecha configurada para el medidor'
                ]
               
            ]);
        }

        if ($request->has('meter_id')) {
            $order->meter_id = $input['meter_id'];
        }
        
        if ($request->has('user_id')) {
            $order->user_id = $input['user_id'];
        }
        
        if ($request->has('finalMeasure')) {
            $order->finalMeasure = $input['finalMeasure'];
        }
        
        if ($order->isClean()) {
            return response()->json([
                'data' => [
                    'ok' => false,
                    'message' => 'Se debe especificar al menos un valor diferente para actualizar',
                    'code' => '422'
                ]
            ], 422);
        } else {

            $order->initialMeasure = $order->previous_measure;

            // Validando la medida sea mayor que la final
            if ($order->finalMeasure < $order->initialMeasure) {
                return response()->json([
                    'data' => [
                        'ok' => false,
                        'message' => 'La lectura actual es menor a la ultima lectura, revise sus datos'
                    ]
                    
                ]);
            }

            $totalMeasure = $input['finalMeasure'] - $order->previous_measure;

            // Validando tabla de tarifas por consumo
            if ($totalMeasure <= 15 ) {
                $order->ammount = 30;
            }

            if ($totalMeasure > 15 && $totalMeasure <= 30) {
                $order->ammount = 50;
            } 

            if ($totalMeasure > 30 && $totalMeasure <= 75) {
                $order->ammount = 100;
            }

            if ($totalMeasure > 75 && $totalMeasure <= 100) {
                $order->ammount = 150;
            }

            if ($totalMeasure > 100) {
                $order->ammount = 250;
            }
        }

        $order->save();

        // Validar si el medidor registra alto consumo
        $altoConsumo = DB::select('select count(*) as cantidad from orders where (finalMeasure - initialMeasure) >= 100 and status = :status and meter_id = :meter', 
                                    ["status" => "Facturado", "meter" => $order->meter_id]);

        if ((int)$altoConsumo[0]->cantidad > 2) {

            $customerBlacklist = new CustomerBlacklist();
            $customerBlacklist->order_id = $order->id;

            $customerBlacklist->save();

            return response()->json([
                'ok' => true,
                'message' => 'Registro modificado satisfactoriamente',
                'data' => $order,
                'warnings' => [
                    'type' => 'info',
                    'messages' => [
                        '1' => 'El medidor No. ' . $order->meter_id . ' fue asignado en la lista negra de clientes morosos'
                    ]
                ]
            ], 201);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro modificado satisfactoriamente',
            'data' => $order
        ], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        $order->delete();

        $order->status = 'Anulado';
        $order->save();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $order
        ], 200);
    }
}
