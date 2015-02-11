<?php namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Controllers\Controller;

use GuzzleHttp\Client;

class WisePayController extends Controller {

    public function check(Request $request){

        // Validate HTTPS connection
        if (!(Request::secure())) {
            return Response::json(['error' => 'true', 'message' => 'The server has rejected an insecure connection.'], 400);
        }

        // Validate request method
        if (!(Request::isMethod('post'))) {
            return Response::json(['error' => 'true', 'message' => 'The server has rejected an improper request method.'], 400);
        }

        // Validate Content-Type
        if ($request->header('Content-Type') != 'application/x-www-form-urlencoded') {
            return Response::json(['error' => 'true', 'message' => 'The server has rejected an improper request header.'], 400);
        }

        // Retrieve and validate credentials from request
        $username = Request::input('username');
        $password = Request::input('password');

        if (empty($username)) {
            return Response::json(['error' => 'true', 'message' => 'The username or password is missing.'], 401);
        }

        if (empty($password)) {
            return Response::json(['error' => 'true', 'message' => 'The username or password is missing.'], 401);
        }

        // Authenticate user
        $client = new GuzzleHttp\Client();

        // TODO: Scrape balances with error checking

        // TODO: Return balances

	}

}
