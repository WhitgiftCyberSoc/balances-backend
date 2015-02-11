<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Class WisePayController
 * @package App\Http\Controllers
 */
class WisePayController extends Controller {


    /**
     * The request sent by the user.
     *
     * @var Request
     */
    private $request;

    public function __construct(Request $request){
        $this->request = $request;
    }

    public function check(){
        // TODO: Return balances
    }

    public function authenticateUser(){
        // TODO: Authenticate user with WisePay website
        $client = new Client();
        $jar = new CookieJar();
        $response = $client->post('https://www.wisepay.co.uk/store/parent/process.asp', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:27.0) Gecko/20100101 Waterfox/27.0 Firefox/27.0.1',
                'Host' => 'www.wisepay.co.uk',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'mID' => '24022',
                'ACT' => 'login',
                'acc_user_email' => $this->request->input('username'),
                'acc_password' => $this->request->input('password'),
                'x' => '0',
                'y' => '0'
            ],
            'cookies' => $jar
        ]);
        $code = $response->getBody();

        //TODO? Timeout exception

        //TODO: Check for non-200 status code

        //TODO: Check for authentication failure

        //TODO: Check for authentication success or log and return an error

        return $code;
    }

    public function scrapeBalances(){
        // TODO: Scrape balances and check for errors
        $code = $this->authenticateUser();
    }

}
