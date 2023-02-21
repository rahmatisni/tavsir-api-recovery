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

    public function cek()
    {
        $payload=array(
            'mitra'=>'DJI',
            'accountID'=>'085640224722',
            'merchantID'=>'DJI000472',
            'merchantName'=>'PT.Testing',
            'counterID'=>'1'
        );
        
        $res = Http::withOptions(['verify' => false,])
                        ->withHeaders(['Authorization' => 'Digest '.$this->getToken()])
                        ->post(env('KIOSBANK_URL').self::SIGN_ON, $payload);

        return $res->json();
    }
}
