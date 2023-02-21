<?php

namespace App\Services\External;

use Illuminate\Support\Facades\Http;

class KiosBankService
{
    function post($url,$header = '',$params=false){
        $curl=curl_init();
    
        if($params===false)
            $query='';
        else 
            $query=json_encode($params);
    
        curl_setopt_array($curl,array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 800,
            CURLOPT_HEADER=>true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => array(
                $header,
                'content-type:application/json'
            ),
            CURLOPT_SSL_VERIFYHOST=>0,
            CURLOPT_SSL_VERIFYPEER=>0
        ));
        $response=curl_exec($curl);
        $err=curl_error($curl);
        curl_close($curl);
        if($err){
            return $err;
        } else {
            return $response;
        }
    }

    public function auth()
    {
        return $this->post(url: env('KIOSBANK_URL'));
    }

}