<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\CookieJar;

use XPathSelector\Selector;
use XPathSelector\Exception\NodeNotFoundException;

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
        $scrapeRequest = $this->scrapeBalances();

        // Pass on previous errors
        if ($scrapeRequest['error'] == 'true') {
            return $scrapeRequest['response'];
        }

        return response()->json(['balances' => $scrapeRequest['balances']], 200);
    }

    public function scrapeBalances()
    {
        $client = new Client();
        $authRequest = $this->authUser();

        // Pass on previous errors
        if ($authRequest['error'] == 'true') {
            return $authRequest;
        }

        // Scrape balances from WisePay website
        $cookieJar = $authRequest['cookie'];
        $response = $client->get('https://www.wisepay.co.uk/store/parent/default.asp?view=account', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:27.0) Gecko/20100101 Waterfox/27.0 Firefox/27.0.1',
                'Host' => 'www.wisepay.co.uk'
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

        // Load HTML into DOM
        $code = $response->getBody();
        $xs = Selector::loadHTML($code);

        // Scrape balances from DOM
        try {
            $balances['lunch'] = $xs->find('//*[@id="page_content"]/table[2]/tbody/tr[2]/td/table[3]/tbody/tr/td/b[1]');
            $balances['tuck'] = $xs->find('//*[@id="page_content"]/table[2]/tbody/tr[2]/td/table[3]/tbody/tr/td/b[2]');
        } catch (NodeNotFoundException $e) {
            return [
                'error' => 'true',
                'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain the balances.'], 500)
            ];
        }

        // Extract double from balances
        foreach ($balances as &$balance) {
            if (preg_match('/((\d+)\.(\d+))/', $balance, $match)) {
                $balance = floatval($match);
            } else {
                return [
                    'error' => 'true',
                    'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain and match the balances.'], 500)
                ];
            }
        }

        return [
            'error' => 'false',
            'balances' => $balances
        ];
    }

    public function authUser()
    {
        $client = new Client();
        $cookieJar = new CookieJar();

        // Authenticate user with WisePay website
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
