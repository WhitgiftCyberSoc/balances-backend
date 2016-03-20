<?php namespace App\Http\Controllers;

use App\Http\Requests;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use XPathSelector\Exception\NodeNotFoundException;
use XPathSelector\Selector;

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

    private $currentHtmlBody;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function checkAuth()
    {
        // Make an authentication attempt and pass on errors
        $authRequest = $this->authUser();

        if ($authRequest['error'] === 'true') {
            return $authRequest['response'];
        } elseif ($authRequest['error'] === 'false') {
            return response()->json(['error' => 'false', 'message' => ''], 200);
        } else {
            return response()->json(['error' => 'true', 'message' => 'The server experienced an unhandled exception.'], 500);
        }
    }

    public function checkBalances()
    {
        // Retrieve HTML from the account page and pass on errors
        $code = $this->getHtmlFromAccountPage();

        if ($code['error'] === 'true') {
            return $code;
        }

        // Retrieve balances and pass on errors
        $balances = $this->scrapeBalancesFromHtml();

        if ($balances['error'] === 'true') {
            return $balances['response'];
        } elseif ($balances['error'] === 'false') {
            return response()->json(['error' => 'false', 'balances' => $balances['balances'], 'message' => ''], 200);
        } else {
            return response()->json(['error' => 'true', 'message' => 'The server experienced an unhandled exception.'], 500);
        }
    }

    public function checkBalancesAndPurchases()
    {
        // Retrieve HTML from the account page and pass on errors
        $code = $this->getHtmlFromAccountPage();

        if ($code['error'] === 'true') {
            return $code;
        }

        // Retrieve purchases and pass on errors
        $purchases = $this->scrapePurchasesFromHtml();
        if ($purchases['error'] === 'true') {
            return $purchases['response'];
        }

        // Retrieve balances and pass on errors
        $balances = $this->scrapeBalancesFromHtml();
        if ($balances['error'] === 'true') {
            return $balances['response'];
        }

        if ($purchases['error'] === 'false' && $balances['error'] === 'false') {
            return response()->json(['error' => 'false', 'balances' => $balances['balances'], 'purchases' => $purchases['purchases'], 'message' => ''], 200);
        } else {
            return response()->json(['error' => 'true', 'message' => 'The server experienced an unhandled exception.'], 500);
        }
    }

    private function scrapeBalancesFromHtml()
    {
        $xs = Selector::loadHTML($this->currentHtmlBody);

        // Scrape balances from DOM
        try {
            $balances['lunch'] = $xs->find('/html/body/div/table/tr[6]/td/table[2]/tr[1]/td/span[2]')->innerHTML();
            $balances['tuck'] = $xs->find('/html/body/div/table/tr[6]/td/table[2]/tr[2]/td/span[2]')->innerHTML();
        } catch (NodeNotFoundException $e) {
            return [
                'error' => 'true',
                'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain the balances.'], 500)
            ];
        }

        // Extract balances as integers
        foreach ($balances as &$balance) {
            if (preg_match('/(\d+)\.(\d+)/', $balance, $match)) {
                $balance = (int)($match[0] * 100);
            } else {
                return [
                    'error' => 'true',
                    'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain and parse the balances.'], 500)
                ];
            }
        }

        return [
            'error' => 'false',
            'balances' => $balances
        ];
    }

    private function scrapePurchasesFromHtml()
    {
        $xs = Selector::loadHTML($this->currentHtmlBody);

        // Scrape purchases from DOM
        $unparsedPurchases = [];

        try {
            $scrapedPurchases = $xs->findAll('/html/body/div/table/tr[6]/td/table[4]/tr/td[2]/table[2]/tr');

            $isSkipped = false;
            foreach ($scrapedPurchases as $scrapedPurchase) {
                if (!$isSkipped) {
                    $isSkipped = true;
                    continue;
                }

                array_push($unparsedPurchases, [
                    'date' => trim($scrapedPurchase->find('td[1]')->innerHTML()),
                    'item' => trim($scrapedPurchase->find('td[2]')->innerHTML()),
                    'price' => trim($scrapedPurchase->find('td[3]')->innerHTML())
                ]);
            }
        } catch (NodeNotFoundException $e) {
            return [
                'error' => 'true',
                'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain the list of purchases.'], 500)
            ];
        }

        // Extract details for each purchase
        $purchases = [];
        foreach ($unparsedPurchases as $unparsedPurchase) {

            // Parse date and time into separate values
            if (preg_match_all('/^(\d{2}\/\d{2}\/\d{4}) (\d{2}\:\d{2}\:\d{2})$/', $unparsedPurchase['date'], $matches)) {
                $date = $matches[1][0];
                $time = $matches[2][0];
            } else {
                return [
                    'error' => 'true',
                    'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain and parse the list of purchases.'], 500)
                ];
            }

            // Parse price
            if (preg_match('/(\d+)\.(\d+)/', $unparsedPurchase['price'], $match)) {
                $price = (int)($match[0] * 100);
            } else {
                return [
                    'error' => 'true',
                    'response' => response()->json(['error' => 'true', 'message' => 'The server has been unable to obtain and parse the list of purchases.'], 500)
                ];
            }

            array_push($purchases, [
                'date' => $date,
                'time' => $time,
                'item' => $unparsedPurchase['item'],
                'price' => $price
            ]);
        }

        return [
            'error' => 'false',
            'purchases' => $purchases
        ];
    }

    private function getHtmlFromAccountPage()
    {
        $client = new Client();
        $authRequest = $this->authUser();

        // Pass on previous errors
        if ($authRequest['error'] === 'true') {
            return $authRequest;
        }

        // Retrieve the account page
        $cookieJar = $authRequest['cookie'];
        $response = $client->get('https://www.wisepay.co.uk/store/parent/default.asp?view=PP&sub=fd', [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.2; Win64; x64; rv:27.0) Gecko/20100101 Waterfox/27.0 Firefox/27.0.1',
                'Referer' => 'https://www.wisepay.co.uk/store/parent/default.asp?view=PP&sub=fd',
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
        $this->currentHtmlBody = $response->getBody();

        return [
            'error' => 'false',
        ];
    }

    private function authUser()
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
                'response' => response()->json(['error' => 'true', 'message' => 'The username or password is incorrect. Please check your credentials and try again.'], 401)->header('WWW-Authenticate', 'Basic realm="fake"')
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
