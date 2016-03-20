<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::post('auth', [
    'middleware' => ['connection', 'auth'],
    'uses' => 'WisePayController@checkAuth'
]);

Route::post('balances', [
    'middleware' => ['connection', 'auth'],
    'uses' => 'WisePayController@checkBalances'
]);

Route::post('all', [
    'middleware' => ['connection', 'auth'],
    'uses' => 'WisePayController@checkBalancesAndPurchases'
]);