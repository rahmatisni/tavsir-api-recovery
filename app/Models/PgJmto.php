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
        if(env('PG_FAKE_RESPON') === true) {
        //for fake
        Http::fake([
            env('PG_BASE_URL').'/oauth/token' => function () {
                return Http::response([
                    'access_token' => 'ini-fake-access-token',
                    "token_type" => "Bearer",
                    "expires_in" => 36000,
                    "scope" => "resource.WRITE resource.READ"
                ], 200);
            },
        ]);
        //end fake
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
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
        ->withoutVerifying()
        ->post(env('PG_BASE_URL') . $path,$payload);
        return $response;
    }

    public static function vaCreate($sof_code,$bill_id,$bill_name,$amount,$desc,$phone,$email,$customer_name)
    {
        $payload = [
            "sof_code" =>  $sof_code,
            "bill_id" =>  $bill_id,
            "bill_name" => $bill_name,
            "amount" => (string) $amount,
            "desc" =>  $desc,
            "exp_date" =>  Carbon::now()->addDay(1)->format('Y-m-d H:i:s'),
            "va_type" =>  "close",
            "phone" =>  $phone,
            "email" =>  $email,
            "customer_name" =>  $customer_name,
            "submerchant_id" => ''
        ];

        if(env('PG_FROM_TRAVOY') === true){
            return Http::withoutVerifying()->post(env('TRAVOY_URL').'/pg-jmto',[
                'method' => 'POST',
                'path' => '/va/create',
                'payload' => $payload
            ])->json();
        }

        if(env('PG_FAKE_RESPON') === true) {
            //for fake 
            $fake_respo_create_va = [
                "status" => "success",
                "rc" => "0000",
                "rcm" => "success",
                "responseData" => [
                    "sof_code" => "BRI",
                    "va_number" => "7777700100299999",
                    "bill" => $payload['amount'],
                    "fee" => "1000",
                    "amount" => (string) $amount + 1000,
                    "bill_id" => $payload['bill_id'],
                    "bill_name" => $payload['bill_name'],
                    "desc" => $payload['desc'],
                    "exp_date" => $payload['exp_date'],
                    "refnum" => "VA".Carbon::now()->format('YmdHis'),
                    "phone" => $payload['phone'],
                    "email" => $payload['email'],
                    "customer_name" => $payload['customer_name'],
                ],
                "requestData" => $payload
            ];

            Http::fake([
                env('PG_BASE_URL').'/va/create' => function () use($fake_respo_create_va){
                    return Http::response($fake_respo_create_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('/va/create', $payload);
        return $res->json();
    }

    public static function vaStatus($sof_code,$bill_id,$va_number,$refnum,$phone,$email,$customer_name)
    {
        $payload = [
            "sof_code" =>  $sof_code,
            "bill_id" =>  $bill_id,
            "va_number" => $va_number,
            "refnum" =>  $refnum,
            "phone" =>  $phone,
            "email" =>  $email,
            "customer_name" =>  $customer_name,
            "submerchant_id" => ''
        ];

        if(env('PG_FROM_TRAVOY') === true){
            return Http::withoutVerifying()->post(env('TRAVOY_URL').'/pg-jmto',[
                'method' => 'POST',
                'path' => '/va/cekstatus',
                'payload' => $payload
            ])->json();
        }

        if(env('PG_FAKE_RESPON') === true) {
            //for fake
            $fake_respon_status_va = [
                "status"=> "success",
                "rc"=> "0000",
                "rcm"=> "success",
                "responseData"=> [
                    "sof_code"=> $payload['sof_code'],
                    "bill_id"=> $payload['bill_id'],
                    "va_number"=> $payload['va_number'],
                    "pay_status"=> "1",
                    "amount"=> "99999.00",
                    "bill_name"=> "FAKE BILL NAME",
                    "desc"=> "FAKE DESC",
                    "exp_date"=> "2022-08-12 00:00:00",
                    "refnum"=> "VA20220811080829999999",
                    "phone"=> $payload['phone'],
                    "email"=> $payload['email'],
                    "customer_name"=> $payload['customer_name'],
                ],
                "requestData"=> $payload
            ];
            Http::fake([
                env('PG_BASE_URL').'/va/cekstatus' => function () use($fake_respon_status_va){
                    return Http::response($fake_respon_status_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('/va/cekstatus', $payload);
        return $res->json();
    }

    public static function vaBriDelete($sof_code,$bill_id,$va_number,$refnum,$phone,$email,$customer_name)
    {
        $payload = [
            "sof_code" =>  $sof_code,
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

    public static function tarifFee($sof_id,$payment_method_id,$sub_merchant_id)
    {
        $payload = [
            "sof_id" =>  $sof_id,
            "payment_method_id" =>  $payment_method_id,
            "sub_merchant_id" =>  $sub_merchant_id,
        ];
        $res = self::service('/sof/tariffee', $payload);
        if($res->successful()){
            return $res->json()['responseData']['value'];
        }
       
        return null;
    }

    public static function feeBriVa()
    {
        return PgJmto::tarifFee(1,2,null);
    }

    public static function feeMandiriVa()
    {
        return PgJmto::tarifFee(3,2,null);
    }

    public static function feeBniVa()
    {
        return PgJmto::tarifFee(4,2,null);
    }
}
