<?php

namespace App\Services\External;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class KiosBankService
{
    function post($url, $header, $params = false)
    {
        $curl = curl_init();

        if ($params === false)
            $query = '';
        else
            $query = json_encode($params);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 800,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => array(
                $header,
                'content-type:application/json'
            ),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        } else {
            return $response;
        }
    }

    function auth_response($params, $uri, $request_method)
    {
        /*
            SESUAIKAN INI
        */
        $username = 'dji';
        $password = 'abcde';
        $nc = '1'; //berurutan 1,2,3..dst sesuai request
        $cnonce = uniqid();

        $a1 = md5($username . ':' . $params['Digest realm'] . ':' . $password);
        $a2 = md5($request_method . ':' . $uri);
        $response = md5($a1 . ':' . $params['nonce'] . ':' . $nc . ':' . $cnonce . ':' . $params['qop'] . ':' . $a2);
        $query = array(
            'username' => $username,
            'password' => $password,
            'realm' => $params['Digest realm'],
            'nonce' => $params['nonce'],
            'uri' => $uri,
            'qop' => $params['qop'],
            'nc' => $nc,
            'cnonce' => $cnonce,
            'opaque' => $params['opaque'],
            'response' => $response
        );
        $query_str = 'username="' . $query['username'] . '",realm="' . $query['realm'] . '",nonce="' . $query['nonce'] . '",uri="' . $query['uri'] . '",qop="' . $query['qop'] . '",nc="' . $query['nc'] . '",cnonce="' . $query['cnonce'] . '",response="' . $query['response'] . '",opaque="' . $query['opaque'] . '"';
        return $query_str;
    }

    public function generateSessionId() : string
    {
        $ip_interface = '10.11.12.5';
        $port_interface = '16551';

        $full_url = 'https://' . $ip_interface . ':' . $port_interface . '/auth/Sign-On';

        $full_url = env('KIOSBANK_URL').'/auth/Sign-On';

        $sign_on_response = $this->post($full_url, '');
        $response_arr = explode('WWW-Authenticate: ', $sign_on_response);

        $response_arr_1 = explode('error', $response_arr[1]);
        $response = trim($response_arr_1[0]);
        $auth_arr = explode(',', $response);
        $auth_sorted = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $auth_sorted[$key] = substr($val, 1, strlen($val) - 2);
        }
        $auth_query = $this->auth_response($auth_sorted, '/auth/Sign-On', 'POST');

        /*
	    SESUAIKAN INI
        */
        $body_params = array(
            'mitra' => 'DJI',
            'accountID' => '085640224722',
            'merchantID' => 'DJI000472',
            'merchantName' => 'PT.Testing',
            'counterID' => '1'
        );
        // $post_response = $this->post($full_url, $post_header, $body_params);
        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $body_params);
        $res_json = $post_response->json();

        return $res_json['SessionID'];
    }

    public function getSeesionId()
    {
        $session = Redis::get('session_kios_bank');
        if(!$session)
        {
            $now = Carbon::now();
            $tomorrow = Carbon::tomorrow()->setMinute(15);
            $diff = $now->diffInMinutes($tomorrow) * 60;
            $session = $this->generateSessionId();
            Redis::set('session_kios_bank',$session,$diff);
            Redis::expire('session_kios_bank',$diff);
        }

        return $session;
    }

    //sesion id
    //CE1CD18DB249ED3D5AC166D2063D7BF5
    public function cek()
    {
       $session_id = $this->getSeesionId();

       return $session_id;
    }
}
