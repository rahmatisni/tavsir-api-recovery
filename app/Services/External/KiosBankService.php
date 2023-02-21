<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class KiosBankService
{
    public function auth()
    {
        // $res = Http::withOptions(['verify' => false,])->get(env('KIOSBANK_URL'));
        // $diges = $res->header('WWW-Authenticate');
        $diges = "Digest realm=\"Design Jaya Indonesia\",qop=\"auth\",nonce=\"4812f6bd4c0d16c54f0199e64bdfb923\",opaque=\"d99cbbdd31259a30b4b50c78bc53d3d3\"";
        $diges = explode(',', $diges);
        $auth_sorted = array();
        foreach ($diges as $auth) {
            list($key, $val) = explode('=', $auth);
            $auth_sorted[$key] = substr($val, 1, strlen($val) - 2);
        }
        $diges;

        $res = Http::withOptions(['verify' => false,])->post(env('KIOSBANK_URL'));

        $auth_header = 'Authorization : Digest '.$diges;


        /*
            SESUAIKAN INI
        */
        $body_params=array(
            'mitra'=>'DJI',
            'accountID'=>'testAccount',
            'merchantID'=>'TST956124',
            'merchantName'=>'PT.Testing',
            'counterID'=>'1'
        );
        $post_response=post($full_url,$post_header,$body_params);
        echo $post_response;
    }
}
