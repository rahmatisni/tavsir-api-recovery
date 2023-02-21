<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class KiosBankService
{
    const SIGN_ON = '/auth/Sign-On';

    public function getDigest()
    {
        $res = Http::withOptions(['verify' => false,])->get(env('KIOSBANK_URL'));
        $diges = $res->header('WWW-Authenticate');
        // $diges = "Digest realm=\"Design Jaya Indonesia\",qop=\"auth\",nonce=\"4812f6bd4c0d16c54f0199e64bdfb923\",opaque=\"d99cbbdd31259a30b4b50c78bc53d3d3\"";
        $data = explode(',', $diges);
        $result = array();
        foreach ($data as $auth) {
            list($key, $val) = explode('=', $auth);
            $result[$key] = substr($val, 1, strlen($val) - 2);
        }
        return $result;
    }

    public function getToken(): string
    {
        $digest = $this->getDigest();

        $method = 'POST';
        $path = '/auth/Sign-On';
        $username = 'dji';
        $password = 'abcde';
        $nc = '1'; //berurutan 1,2,3..dst sesuai request
        $cnonce = uniqid();

        $a1 = md5($username . ':' . $digest['Digest realm'] . ':' . $password);
        $a2 = md5($method . ':' . $path);

        $response = md5($a1 . ':' . $digest['nonce'] . ':' . $nc . ':' . $cnonce . ':' . $digest['qop'] . ':' . $a2);

        return $response;
    }

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

    public function cek()
    {
        // $payload=array(
        //     'mitra'=>'DJI',
        //     'accountID'=>'085640224722',
        //     'merchantID'=>'DJI000472',
        //     'merchantName'=>'PT.Testing',
        //     'counterID'=>'1'
        // );

        // $res = Http::withOptions(['verify' => false,])
        //                 ->withHeaders(['Authorization' => 'Digest '.$this->getToken()])
        //                 ->post(env('KIOSBANK_URL').self::SIGN_ON, $payload);

        // return $res->json();

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

        $post_header = 'Authorization : Digest ' . $auth_query;
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
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query]);
        return $post_response;
    }
}
