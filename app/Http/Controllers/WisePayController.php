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
class WisePayController extends Controller
{

    /**
     * The request sent by the user.
     *
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function check()
    {
        // TODO: Return balances
        $balanceRequest = $this->scrapeBalances();

        // Pass on previous errors
        if ($balanceRequest['error'] == 'true') {
            return $balanceRequest['response'];
        }
    }

    public function scrapeBalances()
    {
        // TODO: Scrape balances and check for errors
        $authRequest = $this->authUser();

        // Pass on previous errors
        if ($authRequest['error'] == 'true') {
            return $authRequest;
        }
    }

    public function authUser()
    {
        $client = new Client();
        $cookieJar = new CookieJar();

        // TODO: Authenticate user with WisePay website
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
                'y' => '0',
                'timeout' => '5'
            ],
            'cookies' => $cookieJar
        ]);

        // Check for non-200 HTTP status code
        if ($response->getStatusCode() != 200) {
            return [
                'error' => 'true',
                'response' => response()->json(['error' => 'true', 'message' => 'The WisePay server returned a non-200 status code - ' . $response->getStatusCode()], 502)
            ];
        }

        // Check for authentication failure
        $code = $response->getBody();

        if (strpos($code, 'Login Failure') !== false) {
            return [
                'error' => 'true',
                'response' => response()->json(['error' => 'true', 'message' => 'The username or password is incorrect. Please check your credentials and try again.'], 401)
            ];
        } elseif (strpos($code, '/parent/process.asp?ACT=logout') !== false) {
            return [
                'error' => 'false',
                'cookie' => $cookieJar
            ];
        } else {
            return [
                'error' => 'true',
                'response' => response()->json(['error' => 'true', 'message' => 'The server experienced an unhandled exception.'], 500)
            ];
        }
    }

}
