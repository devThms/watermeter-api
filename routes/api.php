<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('roles', 'RolesController');
Route::apiResource('users', 'UserController');
Route::apiResource('customers', 'CustomerController');
Route::apiResource('zones', 'ZoneController');
Route::apiResource('meters', 'MeterController');
Route::apiResource('orders', 'OrderController');
Route::apiResource('cash-receipts', 'CashReceiptController');
Route::apiResource('invoices', 'InvoiceController');
Route::apiResource('customer-blacklist', 'CustomerBlacklistController');


