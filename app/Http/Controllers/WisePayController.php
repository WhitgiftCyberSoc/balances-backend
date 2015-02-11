<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GuzzleHttp\Client;

class WisePayController extends Controller {

    public function check(Request $request){

        // Authenticate user
        $client = new Client();

        // TODO: Scrape balances with error checking

        // TODO: Return balances

	}

}
