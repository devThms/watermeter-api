<?php

namespace App\Http\Controllers;

use App\Models\CustomerBlacklist;
use Illuminate\Http\Request;

class CustomerBlacklistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customerBlacklists = CustomerBlacklist::with(['order', 'order.meter', 'order.meter.customer'])->get();

        return response()->json([
            'ok' => true,
            'data' => $customerBlacklists
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CustomerBlacklist  $customerBlacklist
     * @return \Illuminate\Http\Response
     */
    public function show(CustomerBlacklist $customerBlacklist)
    {
        if (is_null($customerBlacklist)) {

            return response()->json([
                ['error' => 'Recurso no encontrado']
            ], 404);

        }

        return response()->json([
            'ok' => true,
            'message' => 'Registro encontrado',
            'data' => $customerBlacklist
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CustomerBlacklist  $customerBlacklist
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CustomerBlacklist $customerBlacklist)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CustomerBlacklist  $customerBlacklist
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerBlacklist $customerBlacklist)
    {
        $customerBlacklist->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Registro eliminado satisfactoriamente',
            'data' => $customerBlacklist
        ], 200);
    }
}
