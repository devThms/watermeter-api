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

Route::prefix('v1/auth')->group( function() {
    Route::post('login', 'AuthController@login')->name('auth.login');
    Route::post('logout', 'AuthController@logout')->name('auth.logout');
    Route::post('refresh', 'AuthController@refresh')->name('auth.refresh');
    Route::post('show', 'AuthController@show')->name('auth.show');
});

Route::group(['middleware' => 'cors'], function() {

    Route::apiResource('users', 'UserController');

    Route::apiResource('roles', 'RolesController');

    Route::post('users/login', 'UserController@login')->name('users.login');

    Route::apiResource('customers', 'CustomerController');
    Route::apiResource('zones', 'ZoneController');

    Route::apiResource('meters', 'MeterController');
    Route::get('meters/zone/{zone}', 'MeterController@meter_zone')->name('meters.zone');

    Route::apiResource('orders', 'OrderController');
    Route::get('orders/order-log/{meter}', 'OrderController@order_log')->name('orders.log');
    Route::get('orders/pending-payment/{meter}', 'OrderController@pending_payment')->name('orders.pending');

    Route::apiResource('cash-receipts', 'CashReceiptController');
    Route::get('cash-receipts/receipt-log/{meter}', 'CashReceiptController@receipt_log')->name('cash-receipts.log');
    Route::get('cash-receipts/pending-payment/{meter}', 'CashReceiptController@pending_payment')->name('cash-receipts.pending');

    Route::apiResource('invoices', 'InvoiceController');
    Route::post('invoices/payment/{invoice}', 'InvoiceController@payment')->name('invoices.pay');
    Route::post('invoices/cancel/{invoice}', 'InvoiceController@cancel')->name('invoices.cancel');
    Route::get('invoices/invoice-log/{meter}', 'InvoiceController@invoice_log')->name('invoices.log');
    Route::get('invoices/pending-payment/{meter}', 'InvoiceController@pending_payment')->name('invoices.pending');
    Route::get('invoices/report/{user}/{from}/{to}', 'InvoiceController@report')->name('invoices.report');

    Route::apiResource('customer-blacklist', 'CustomerBlacklistController');
    
});






