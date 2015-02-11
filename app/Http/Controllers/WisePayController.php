<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GuzzleHttp\Client;

class WisePayController extends Controller {

    public function check(Request $request){

        // Validate HTTPS connection
        if (!($request->secure())) {
            return response()->json(['error' => 'true', 'message' => 'The server has rejected an insecure connection.'], 400);
        }

        // Validate request method
        if (!($request->isMethod('post'))) {
            return response()->json(['error' => 'true', 'message' => 'The server has rejected an improper request method.'], 400);
        }

        // Validate Content-Type
        if ($request->header('Content-Type') != 'application/x-www-form-urlencoded') {
            return response()->json(['error' => 'true', 'message' => 'The server has rejected an improper request header.'], 400);
        }

        // Retrieve and validate credentials from request
        $username = $request->input('username');
        $password = $request->input('password');

        if (empty($username)) {
            return response()->json(['error' => 'true', 'message' => 'The username or password is missing.'], 401);
        }

        if (empty($password)) {
            return response()->json(['error' => 'true', 'message' => 'The username or password is missing.'], 401);
        }

        // Authenticate user
        $client = new GuzzleHttp\Client();

        // TODO: Scrape balances with error checking

        // TODO: Return balances

	}

}
