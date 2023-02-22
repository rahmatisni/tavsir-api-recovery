<?php

namespace App\Services\External;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class KiosBankService
{
    protected $accountKisonBank;

    function __construct()
    {
        $this->accountKisonBank = [
            'mitra' => env('KIOSBANK_MITRA'),
            'accountID' => env('KIOSBANK_ACCOUNT_ID'),
            'merchantID' => env('KIOSBANK_MERCHANT_ID'),
            'merchantName' => env('KIOSBANK_MERCHANT_NAME'),
            'counterID' => env('KIOSBANK_COUNTER_ID')
        ];
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

    public function signOn() : string
    {
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
        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $this->accountKisonBank);
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
            $session = $this->signOn();
            Redis::set('session_kios_bank',$session);
            Redis::expire('session_kios_bank',$diff);
        }

        return $session;
    }

    public function cekStatusProduct()
    {
        $full_url = env('KIOSBANK_URL').'/Services/get-Active-Product';

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
        $auth_query = $this->auth_response($auth_sorted, '/Services/get-Active-Product', 'POST');

        /*
	    SESUAIKAN INI
        */
        $body_params=array(
            'sessionID'=> $this->getSeesionId(),
            ...$this->accountKisonBank
        );
        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $body_params);
        $res_json = $post_response->json();

        return $res_json;
    }

    public function getProduct()
    {
        // $product = $this->cekStatusProduct();
        $product = [
            "21113E142067F9FD15BDCC991FCEBE99",
            [
                "rc" => "00",
                "active" => "5D5011,5D4521,5D4531,5D4611,5D4621,5D4631,5D4711,5D4731,5D4811,5D4821,5D4831,5D4911,5D4921,5D4931,5D4511,5D5031,5D5111,5D5121,5D5131,5D5211,5D5221,5D5231,5D5311,5D5321,5D5331,5D5431,5D5811,5D5911,5D3731,5D3121,5D3131,5D3151,5D3221,5D3231,5D3321,5D3331,5D3421,5D3431,5D3521,5D3531,5D3621,5D3721,5D6011,5D3821,5D3831,5D3921,5D3931,5D4021,5D4031,5D4121,5D4221,5D4231,5D4311,5D4321,5D4411,5D4431,755031,753051,753061,753071,753081,753091,754001,754021,754031,754041,754061,754071,755001,755021,753041,755081,755171,755191,755211,755221,756001,756011,756021,756051,756111,756121,756131,756141,751111,5D6111,5D6211,5D6311,751011,751021,751031,751041,751051,751061,751071,751081,751091,751101,5D3051,751121,751131,752001,752011,752021,752031,752041,752051,753001,753011,753021,753031,5D0181,505081,510011,510021,510031,510041,510051,510081,520011,520021,550041,560031,5D0111,5D0151,505051,5D0211,5D0251,5D0281,5D0311,5D0351,5D0381,5D0421,5D0451,5D0481,5D0511,5D0581,5D0681,5D0711,502021,500511,500521,500531,500541,500551,500581,501011,501021,501031,501041,501051,501081,502001,5D0781,502041,502081,502511,502521,502531,502541,502551,502581,505001,505011,505021,505031,505041,5D2531,5D1821,5D1831,5D1921,5D1931,5D1941,5D1981,5D2021,5D2031,5D2041,5D2081,5D2121,5D2221,5D2321,5D1781,5D2631,5D2721,5D2731,5D2751,5D2821,5D2831,5D2851,5D2911,5D2921,5D2931,5D2951,5D3021,5D3031,5D1441,5D0811,5D0911,5D0931,5D1011,5D1031,5D1111,5D1131,5D1141,5D1211,5D1221,5D1231,5D1421,5D1431,500501,5D1481,5D1511,5D1521,5D1531,5D1541,5D1581,5D1621,5D1631,5D1681,5D1721,5D1731,5D1741,",
                "maintenance" => ""
            ]
        ];
        $status_respon = $product[1]['rc'] ?? '';
        
        if($status_respon == '00')
        {
            $data = ProductKiosBank::get();

            $active = $product[1]['active'];
            $active =  explode(',',$active);
            foreach ($data as $key => $value) {
                $value->status = false;
            }

            if(count($active) > 1)
            {
                foreach ($active as $key => $value) {
                    foreach ($data as $k => $v) {
                       if($value == $v->kode)
                       {
                         $v->status = true;
                       }
                    }
                }
            }
        }
        return $data;
    }

    public function showProduct($id)
    {
        $product = ProductKiosBank::findOrFail($id);
        return $product;
    }


    public function cek()
    {
        $cek =  $this->getProduct();
       
        return $cek;
    }
}
