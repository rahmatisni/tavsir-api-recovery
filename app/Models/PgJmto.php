<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
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
        ])->post('https://api-jmto.onbilling.id/oauth/token', [
            'grant_type' => 'client_credentials'
        ]);

        return $response->json();
    }

    public static function generateSignature($http_method, $path, $token, $timestamp, $request_body)
    {
        //data you want to sign
        empty($request_body) ? $request_body = '' : $request_body = '';

        $data = $http_method . ':' . $path . ':' . 'Bearer ' . $token . ':' . hash('sha256', $request_body) . ':' . $timestamp;
        
        $privateKey = env('PG_PRIVATE_KEY');
        openssl_sign($data, $signature, $privateKey, 'SHA256');
        return Base64::encode($signature);
    }

    public static function service($method, $path, $payload)
    {
        $data = [
            'method' => $method,
            'path' => $path,
            'payload' => $payload,
        ];
        $res = Http::post(env('TRAVOY_API_URL') . '/pg-jmto', $data);

        return $res->json();

        // $token = self::getToken();
        // $timestamp = Carbon::now()->format('c');


        // $signature = self::generateSignature($method, $path, $token['access_token'], $timestamp, $payload);
        // $response = Http::withHeaders([
        //     'Authorization' => 'Bearer ' . $token['access_token'],
        //     'Accept' => 'application/json',
        //     'Content-Type' => 'application/json',
        //     'JMTO-TIMESTAMP' => $timestamp,
        //     'JMTO-SIGNATURE' => $signature,
        //     'JMTO-DEVCE-ID' => 'POSTMAN',
        //     'CHANNEL-ID' => 'PC',
        //     'JMTO-LATITUDE' => '+40.75',
        //     'JMTO-LONGITUDE' => '-074.00',
        // ])
        //     ->send($method, env('PG_BASE_URL') . $path, $payload);
        // return $response->json();
    }

    public static function vaBriCreate($bill_id,$bill_name,$amount,$desc,$phone,$email,$customer_name)
    {
        $payload = [
            "sof_code" =>  "BRI",
            "bill_id" =>  $bill_id,
            "bill_name" => $bill_name,
            "amount" => (string) $amount,
            "desc" =>  $desc,
            "exp_date" =>  Carbon::now()->addDay(1)->format('Y-m-d H:i:s'),
            "va_type" =>  "close",
            "phone" =>  $phone,
            "email" =>  $email,
            "customer_name" =>  $customer_name,
            "submerchant_id" => '98'
        ];
        $data = [
            'method' => 'POST',
            'path' => '/va/create',
            'payload' => $payload,
        ];
        $res = Http::post(env('TRAVOY_API_URL') . '/pg-jmto', $data);

        return $res->json();
    }

    public static function vaBriStatus($bill_id,$va_number,$refnum,$phone,$email,$customer_name)
    {
        $payload = [
            "sof_code" =>  "BRI",
            "bill_id" =>  $bill_id,
            "va_number" => $va_number,
            "refnum" =>  $refnum,
            "phone" =>  $phone,
            "email" =>  $email,
            "customer_name" =>  $customer_name,
        ];
        $data = [
            'method' => 'POST',
            'path' => '/va/cekstatus',
            'payload' => $payload,
        ];
        $res = Http::post(env('TRAVOY_API_URL') . '/pg-jmto', $data);
        return $res->json();
    }

    public static function vaBriDelete($payload = [])
    {
        $data = [
            'method' => 'POST',
            'path' => '/va/delete',
            'payload' => $payload,
        ];
        $res = Http::post(env('TRAVOY_API_URL') . '/vpg-jmto', $data);
        
        return $res->json();
    }
}
