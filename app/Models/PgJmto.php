<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use ParagonIE\ConstantTime\Base64;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;

class PgJmto extends Model
{
    public static function getToken()
    {
        $response = Http::withHeaders([
            'Accept' => 'application/sjon',
            'Authorization' => 'Basic ' . base64_encode(env('PG_CLIENT_ID') . ':' . env('PG_CLIENT_SECRET')),
            'Content-Type' => 'application/json',
        ])
        ->withoutVerifying()
        ->post(env('PG_BASE_URL').'/oauth/token', ['grant_type' => 'client_credentials']);
        return $response->json();
    }

    public static function generateSignature($path, $token, $timestamp, $request_body)
    {
        //data you want to sign
        if(empty($request_body)){
            $request_body = ['dumy' => 'abc'];
        } else {
            $request_body = json_encode($request_body);
        }

        $data = 'POST' . ':' . $path . ':' . 'Bearer ' . $token . ':' . hash('sha256', $request_body) . ':' . $timestamp;
        
        $privateKey = env('PG_PRIVATE_KEY');
        $publicKey = env('PG_PUBLIC_KEY');
        openssl_sign($data, $signature, $privateKey, 'sha256WithRSAEncryption');
        $sign = Base64::encode($signature);
        $verify = openssl_verify($data, $signature, $publicKey, 'sha256WithRSAEncryption');
        // dd($verify);
        return $sign;
    }

    public static function service($path, $payload)
    {
        $token = self::getToken();
        if(!$token){
            throw new Exception("token not found");
        }
        $timestamp = Carbon::now()->format('c');
        $signature = self::generateSignature($path, $token['access_token'], $timestamp, $payload);
        $response = Http::withHeaders([
            'JMTO-TIMESTAMP' => $timestamp,
            'JMTO-SIGNATURE' => $signature,
            'JMTO-DEVICE-ID' => env('PG_DEVICE_ID','123456789'),
            'CHANNEL-ID' => 'PC',
            'JMTO-LATITUDE' => '106.8795316',
            'JMTO-LONGITUDE' => '-6.2927969',
            'Content-Type' => 'Application/json',
            'Authorization' => 'Bearer ' . $token['access_token'],
        ])
        // ->withoutVerifying()
        ->post(env('PG_BASE_URL') . $path,$payload);
        return $response;
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
        $res = self::service('/va/create', $payload);
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
        $res = self::service('/va/cekstatus', $payload);
        return $res->json();
    }

    public static function vaBriDelete($bill_id,$va_number,$refnum,$phone,$email,$customer_name)
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
        $res = self::service('/va/delete', $payload);
        return $res->json();
    }
}
