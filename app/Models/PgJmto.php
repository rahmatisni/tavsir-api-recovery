<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use ParagonIE\ConstantTime\Base64;

class PgJmto extends Model
{
   public static function getToken()
   {
        $response = Http::withHeaders([
            'Accept' => 'application/sjon',
            'Authorization' => 'Basic ' . base64_encode(env('PG_CLIENT_ID') . ':' . env('PG_CLIENT_SECRET')),
            'Content-Type' => 'application/json',
        ])->post('https://api-jmto.onbilling.id/oauth/token',[
            'grant_type' => 'client_credentials'
        ]);

        return $response->json();
   }

   public static function generateSignature($http_method, $path, $token, $timestamp, $request_body)
   {
        empty($request_body) ? $request_body = '' : $request_body = json_encode($request_body);
        $bodyHas = hash('sha256', $request_body);
        $payload = $http_method.':'.$path.':'.'Bearer '.$token.':'.$bodyHas.':'.$timestamp;
        $algo = "sha256WithRSAEncryption";
        openssl_sign($payload, $binary_signature, env('PG_PRIVATE_KEY'), $algo);
        $binary_signature = Base64::encode($binary_signature);
        return $binary_signature;
   }

   public static function service($method,$path,$body = [])
   {
        $token = self::getToken();
        $timestamp = Carbon::now()->format('c');
        $signature = self::generateSignature($method, $path, $token['access_token'], $timestamp, $body);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'JMTO-TIMESTAMP' => $timestamp,
            'JMTO-SIGNATURE' => $signature,
            'JMTO-DEVCE-ID' => 'POSTMAN',
            'CHANNEL-ID'=> 'PC',
            'JMTO-LATITUDE'=> '+40.75',
            'JMTO-LONGITUDE'=> '-074.00',
        ])->send($method,env('PG_BASE_URL') . $path,$body);
        return $response->json();
   }
}

