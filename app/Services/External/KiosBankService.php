<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class KiosBankService
{
    const SIGN_ON = '/auth/Sign-On';
    
    public function getDigest()
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
        return $diges;
    }

    public function authDigest($diges) : string
    {
        $method = 'POST';
        $path = '/auth/Sign-On';
        $username='dji';
        $password='abcde';
        $nc='1';//berurutan 1,2,3..dst sesuai request
        $cnonce=uniqid();

        $a1=md5($username.':'.$diges['Digest realm'].':'.$password);
        $a2=md5($method.':'.$path);

	    $response=md5($a1.':'.$diges['nonce'].':'.$nc.':'.$cnonce.':'.$diges['qop'].':'.$a2);
        return $response;
    }

    public function cek()
    {
        $digest = $this->getDigest();
        $auth_digest = $this->authDigest($digest);

        return $auth_digest;
    }
}
