<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;

class GoogleAuthController extends Controller
{
    protected  $client;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig('client_secret.json');
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $guzzleClient = new \GuzzleHttp\Client(array('curl' => array(CURLOPT_SSL_VERIFYPEER => false)));
        $client->setHttpClient($guzzleClient);
        $this->client = $client;
    }

    /**
     * Login client using the google account and returns the required header for API
     * Once client is login the returned response from google should be added in header for every request
     * Param for headers are access_token, token_type, expires_in, created
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function login()
    {
        try {
            $return_url = action('Auth\GoogleAuthController@login');
            $this->client->setRedirectUri($return_url);
            if (!isset($_GET['code'])) {
                $auth_url = $this->client->createAuthUrl();
                $filtered_url = filter_var($auth_url, FILTER_SANITIZE_URL);
                return redirect($filtered_url);
            } else {
                $this->client->authenticate($_GET['code']);
                $access_token = $this->client->getAccessToken();
                $result['status'] = 500;
                $result['message'] = 'Login Successful';
                $result['access_token'] = $access_token;
                $result['client_id'] = $this->client->getClientId();
                return response()->json($result);
            }
        } catch (\Exception $e) {
            $result['status'] = 500;
            $result['message'] = $e->getMessage();
            return response()->json($result);
        }

    }
}
