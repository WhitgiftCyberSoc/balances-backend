<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use GuzzleHttp\Client;

class WisePayController extends Controller {

    public function check(Request $request){

        // Validate HTTPS connection
        if (!(Request::secure())) {
            return Response::json(['error' => 'true', 'message' => 'The server has rejected an insecure connection.'], 400);
        }

        // Validate Content-Type
        if ($request->header('Content-Type') != 'application/x-www-form-urlencoded') {
            return Response::json(['error' => 'true', 'message' => 'There has been an issue handling the request.'], 400);
        }

        // TODO: Authenticate user

        // TODO: Scrape balances with error checking

        // TODO: Return balances

	}

}
