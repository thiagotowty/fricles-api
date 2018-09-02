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

Route::group(['prefix' => 'webhook'], function ()
{
    Route::get('', 'FacebookController@webhookGET');
    Route::post('', 'FacebookController@webhookPOST');
});

Route::get('debits/{placa}', 'FacebookController@getDebits');
Route::get('payment/{amont}', 'FacebookController@paymentAtar');
