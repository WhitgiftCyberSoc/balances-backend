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

Route::post('check', [
    // 'middleware' => 'auth', // TODO: Enable middleware once finished
     'uses' => 'WisePayController@check'
//    'uses' => 'WisePayController@scrapeBalances'
]);