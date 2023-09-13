<?php

namespace App\Models;

use App\Models\Dto\BindingDto;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ParagonIE\ConstantTime\Base64;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use Illuminate\Support\Str;

class PgJmto extends Model
{

    public static function getToken()
    {
        $token = Redis::get('token_pg');
        if (!$token) {
            $now = Carbon::now();
            $hours = Carbon::now()->addMinute(59);
            $diff = $now->diffInMinutes($hours) * 60;
            $token = self::generateToken()['access_token'] ?? '';
            if($token == ''){
                // throw new Exception("token not found",422);
            }
            Redis::set('token_pg', $token);
            Redis::expire('token_pg', $diff);
        }
        return $token;
    }
    
    public static function generateToken()
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/oauth/token' => function () {
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

        clock()->event('oauth token')->color('purple')->begin();
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(env('PG_CLIENT_ID') . ':' . env('PG_CLIENT_SECRET')),
            'Content-Type' => 'application/json',
        ])
            ->withoutVerifying()
            ->post(env('PG_BASE_URL') . '/oauth/token', ['grant_type' => 'client_credentials']);
        clock()->event("oauth token")->end();
        return $response->json();
    }

    public static function generateSignature($method ,$path, $token, $timestamp, $request_body)
    {
        $request_body = json_encode($request_body);

        if($method == 'GET')
        {
            $request_body = '';
        }
        $has_body = hash('sha256', $request_body);

        $data = $method . ':' . $path . ':' . 'Bearer ' . $token . ':' . $has_body . ':' . $timestamp;

        $privateKey = env('PG_PRIVATE_KEY');
        $publicKey = env('PG_PUBLIC_KEY');
        openssl_sign($data, $signature, $privateKey, 'sha256WithRSAEncryption');
        $sign = Base64::encode($signature);
        $verify = openssl_verify($data, $signature, $publicKey, 'sha256WithRSAEncryption');
        return $sign;
    }

    public static function service($method, $path, $payload)
    {
        $token = self::getToken();
        $timestamp = Carbon::now()->format('c');
        $signature = self::generateSignature($method, $path, $token, $timestamp, $payload);
        switch ($method) {
            case 'POST':
                clock()->event("pg{$path}")->color('purple')->begin();
                $response = Http::withHeaders([
                    'JMTO-TIMESTAMP' => $timestamp,
                    'JMTO-SIGNATURE' => $signature,
                    'JMTO-DEVICE-ID' => env('PG_DEVICE_ID', '123456789'),
                    'CHANNEL-ID' => 'PC',
                    'JMTO-LATITUDE' => '106.8795316',
                    'JMTO-LONGITUDE' => '-6.2927969',
                    'Content-Type' => 'Application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'JMTO-IP-CLIENT' => '172.0.0.1',
                    'JMTO-REQUEST-ID' => '123456789',
                ])
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->post(env('PG_BASE_URL') . $path, $payload);
                clock()->event("pg{$path}")->end();

                $bad = $response->getStatusCode();
                // dd($asd);
                if($bad === 400) {
                    $fake_respo_create_bad = [
                        "status" => 400,
                        "responseData" => [
                            "is_presentage" => null,
                            "value" => null
                        ]
                    ];
    
                   return $fake_respo_create_bad;  
                   break;     
                }
                return $response;
                break;
            case 'GET':
                $response = Http::withHeaders([
                    'JMTO-TIMESTAMP' => $timestamp,
                    'JMTO-SIGNATURE' => $signature,
                    'JMTO-DEVICE-ID' => env('PG_DEVICE_ID', '123456789'),
                    'CHANNEL-ID' => 'PC',
                    'JMTO-LATITUDE' => '106.8795316',
                    'JMTO-LONGITUDE' => '-6.2927969',
                    'Content-Type' => 'Application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'JMTO-IP-CLIENT' => '172.0.0.1',
                    'JMTO-REQUEST-ID' => '123456789',
                ])
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->get(env('PG_BASE_URL') . $path, $payload);

                return $response;
            
            default:
                # code...
                break;
        }
       
    }

    public static function vaCreate($sof_code, $bill_id, $bill_name, $amount, $desc, $phone, $email, $customer_name, $sub_merchant_id)
    {
        if($amount > 1000000)
        {
            throw new Exception("The amount must be less than 1000000", 422);
        }

        $payload = [
            "sof_code" =>  $sof_code,
            "bill_id" =>  $bill_id,
            "bill_name" => $bill_name,
            "amount" => (string) $amount,
            "desc" =>  $desc,
            "exp_date" =>  Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
            "va_type" =>  "close",
            "phone" =>  $phone,
            "email" =>  $email,
            "customer_name" =>  $customer_name,
            "submerchant_id" => $sub_merchant_id
        ];

        if (env('PG_FROM_TRAVOY') === true) {
            return Http::withoutVerifying()->post(env('TRAVOY_URL') . '/pg-jmto', [
                'method' => 'POST',
                'path' => '/va/create',
                'payload' => $payload
            ])->json();
        }

        if (env('PG_FAKE_RESPON') === true) {
            $fake_respo_create_va = [
                "status" => "success",
                "rc" => "0000",
                "rcm" => "success",
                "responseData" => [
                    "sof_code" => $sof_code,
                    "va_number" => "7777700100299999",
                    "bill" => $payload['amount'],
                    "fee" => "1000",
                    "amount" => (string) $amount + 1000,
                    "bill_id" => $payload['bill_id'],
                    "bill_name" => $payload['bill_name'],
                    "desc" => $payload['desc'],
                    "exp_date" => $payload['exp_date'],
                    "refnum" => "VA" . Carbon::now()->format('YmdHis'),
                    "phone" => $payload['phone'],
                    "email" => $payload['email'],
                    "customer_name" => $payload['customer_name'],
                ],
                "requestData" => $payload
            ];

            Http::fake([
                env('PG_BASE_URL') . '/va/create' => function () use ($fake_respo_create_va) {
                    return Http::response($fake_respo_create_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('POST','/va/create', $payload);
        Log::info($payload);
        Log::info('Va create res', $res->json());
        return $res->json();
    }

    public static function vaStatus($sof_code, $bill_id, $va_number, $refnum, $phone, $email, $customer_name, $submerchant_id)
    {
        $payload = [
            "sof_code" =>  $sof_code,
            "bill_id" =>  $bill_id,
            "va_number" => $va_number,
            "refnum" =>  $refnum,
            "phone" =>  $phone,
            "email" =>  $email,
            "customer_name" =>  $customer_name,
            "submerchant_id" => $submerchant_id ?? ''
        ];

        if (env('PG_FROM_TRAVOY') === true) {
            return Http::withoutVerifying()->post(env('TRAVOY_URL') . '/pg-jmto', [
                'method' => 'POST',
                'path' => '/va/cekstatus',
                'payload' => $payload
            ])->json();
        }

        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            $fake_respon_status_va = [
                "status" => "success",
                "rc" => "0000",
                "rcm" => "success",
                "responseData" => [
                    "sof_code" => $payload['sof_code'],
                    "bill_id" => $payload['bill_id'],
                    "va_number" => $payload['va_number'],
                    "pay_status" => "1",
                    "amount" => "99999.00",
                    "bill_name" => "FAKE BILL NAME",
                    "desc" => "FAKE DESC",
                    "exp_date" => "2022-08-12 00:00:00",
                    "refnum" => "VA20220811080829999999",
                    "phone" => $payload['phone'],
                    "email" => $payload['email'],
                    "customer_name" => $payload['customer_name'],
                ],
                "requestData" => $payload
            ];
            Http::fake([
                env('PG_BASE_URL') . '/va/cekstatus' => function () use ($fake_respon_status_va) {
                    return Http::response($fake_respon_status_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('POST','/va/cekstatus', $payload);
        // dd ($res->json());
        Log::info(['Payload =>', $payload, 'Va status => ', $res->json()]);
        return $res->json();
    }

    public static function vaBriDelete($sof_code, $bill_id, $va_number, $refnum, $phone, $email, $customer_name)
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
        $res = self::service('POST','/va/delete', $payload);
        Log::info('Va delete', $res->json());

        return $res->json();
    }

    public static function tarifFee($sof_id, $payment_method_id, $sub_merchant_id, $bill_amount)
    {
        $payload = [
            "sof_id" =>  $sof_id,
            "payment_method_id" =>  $payment_method_id,
            "sub_merchant_id" =>  $sub_merchant_id,
            "bill_amount" =>  $bill_amount,
        ];
        $res = self::service('POST','/sof/tariffee', $payload);
        if($res['status'] == 400){
            // return $res['responseData'];
            $res = $res['responseData'];
            Log::warning('Trace PG Tarif Fee', $res);

            return $res;
        }
        if ($res->successful()) {
            if($res->json()['status'] == 'ERROR'){
                Log::warning('PG Tarif Fee', $res->json());
                return null;
            }
            Log::warning('Trace PG Tarif Fee', $res);

            return $res->json()['responseData'];
        }
        return null;
    }

    public static function bindDD($payload)
    {
        if (env('PG_FAKE_RESPON') === true) {
            $mandiri_page = "<form name=\"frm_request\" id=\"frm_request\" action=\"https://dev.yokke.bankmandiri.co.id:9773/MTIDDPortal/registration\" method=\"post\">
                <input type=\"hidden\" name=\"signature\" value=\"9aac4ee218d861f9dd220d5a98debdb680ec43fe82dc7ea2d3b1eae765e6cb55c84e95b28f01c1b67f11e8fd11d788a53f1e5d0dddac2345494cdbff5315eb9e\"/>
                <input type=\"hidden\" name=\"merchantID\" value=\"000071000022169\"/>
                <input type=\"hidden\" name=\"requestID\" value=\"1052479112\"/>
                <input type=\"hidden\" name=\"jwt\" value=\"eyJraWQiOiJzc29zIiwiYWxnIjoiUlM1MTIifQ.eyJzdWIiOiJkOGExMmM4MS1iOWQ2LTQ3ZTctOTk3NC0yZjBiZTBiOWYwZGQiLCJhdWQiOm51bGwsIm5iZiI6MTY2NzUyNzY3OCwiaXNzIjoiSldUTVRJIiwiZXhwIjoxNjY3NTI4NTc4LCJpYXQiOjE2Njc1Mjc2Nzh9.jITAwxBvz3IAahi3CYJyGdEHwDTOrnj7we4aD3SD8fS26-3_XcrcACU3R_6rFKCFB-h6MUIBIflGH-fgWJfsdEdKyVJzbzc8KHXcrnkeDsJ0yathk4OkPWwcojq0PPDpiJGukH1afHxVQfCtlifvK2oUImqjY6pXgxMbHLxMnxizl4rbGKdCvBOl6ZoTmawqlMqadyco_7XFMe09Kv4Y-iLzFiSS5Puxb4HxcQjG6wIHq04610QpiUIm9GQSFImelBEvRAB4VM8LUDrZ2sJ90WbKYYmSWRu5QK0bUSZmOHvXVzLJKaKVuXG96KHwKdna-iuATQYNwDAGT0iJRPr77A\"/>
                <input type=\"hidden\" name=\"language\" value=\"ID\"/>
                <input type=\"hidden\" name=\"isBindAndPay\" value=\"N\"/>
                <input type=\"hidden\" name=\"publicKey\" value=\"MIIBCQKCAQCUFOPYrm95cRxbEymJqLgtFWPsddKJIskOknNsdnVzVZdJJijnTliIU/Zw7ryVyTJgZkUv/NhK6qxfkm5Fv7UMMNFFDfWjfFkl2vydMbMD+3rec4C0pgTWFRe418LPPDF/RzZZ/bUG3WM1uyvCVpRMEmogXHCjru4P7LRBcOCMSsUl39j0rIDP9gX2/kjeLIWHYPi2+Dy2r4b0KoSidjRxxOX40+y6McCATBl5//eU6MxxKz2gFnkn3JKDcqvHEYimhWBL66TGjEfHCx8Z3NeaW3OYJ2BSb4svBwROnfD4xJ+UjW3Wm8uFYiGmokskuN4uFoyzFqSvtmy1f50xZ8AVAgMBAAE=\"/>
                <input type=\"hidden\" name=\"terminalID\" value=\"73001308\"/>
                <input type=\"hidden\" name=\"additionalData\" value=\"{&quot;userID&quot;:&quot;JASAMARGA&quot;}\"/>
                <input type=\"hidden\" name=\"tokenRequestorID\" value=\"JASAMARGA\"/>
                <input type=\"hidden\" name=\"journeyID\" value=\"BIND636473807eaeb\"/></form><script>window.onload = function(){document.forms['frm_request'].submit();}</script>";
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/sof/bind' => function () use ($payload, $mandiri_page) {
                    $responseData = [
                        "sof_code" => $payload['sof_code'],
                        "card_no" => $payload['card_no'],
                        "phone" => $payload['phone'],
                        "email" => $payload['email'],
                        "customer_name" => $payload['customer_name'],
                        "refnum" => "BIND" . Str::lower(Str::random(13))
                    ];
                    if ($payload['sof_code'] == 'MANDIRI') {
                        $responseData['landing_page_form'] = $mandiri_page;
                    }
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "success",
                        "responseData" => $responseData,
                        "requestData" => $payload
                    ], 200);
                },
            ]);
            //end fake
        }
        $payload = [
            "sof_code" => $payload['sof_code'],
            "card_no" => $payload['card_no'],
            "phone" => $payload['phone'],
            "email" => $payload['email'],
            "customer_name" => $payload['customer_name'],
            "submerchant_id" => null,
            "exp_date" => $payload['exp_date'],
            "custom_field_1"=> "test",
            "custom_field_2"=>"",
            "custom_field_3"=>"",
            "custom_field_4"=>"",
            "custom_field_5"=>""
        ];
        $res = self::service('POST','/sof/bind', $payload);
        Log::info('DD bind', $res->json());
        return $res;
    }

    public static function bindValidateDD($payload)
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/sof/bind-validate' => function () use ($payload) {
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "binding success",
                        "responseData" => [
                            "sof_code" => $payload['sof_code'],
                            "card_no" => $payload['card_no'],
                            "phone" => $payload['phone'],
                            "email" => $payload['email'],
                            "customer_name" => $payload['customer_name'],
                            "bind_id" => rand(1, 999)
                        ],
                        "request" => $payload
                    ], 200);
                },
            ]);
            //end fake
        }
        $res = self::service('POST','/sof/bind-validate', $payload);
        Log::info('DD bind validate', $res->json());
        return $res;
    }

    public static function unBindDD($payload)
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/sof/unbind' => function () use ($payload) {
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "success",
                        "responseData" => [
                            "sof_code" => $payload['sof_code'],
                            "card_no" => $payload['card_no'],
                            "email" => $payload['email'],
                            "phone" => $payload['phone'],
                            "customer_name" => $payload['customer_name'],
                        ],
                        "requestData" => $payload
                    ], 200);
                },
            ]);
            //end fake
        }
        $res = self::service('POST','/sof/unbind', $payload);
        Log::info('DD unbind', $res->json());
        return $res;
    }

    public static function inquiryDD($payload)
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/directdebit/inquiry' => function () use ($payload) {
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "success",
                        "responseData" => [
                            "sof_code" => $payload['sof_code'],
                            "card_no" => $payload['card_no'],
                            "bill" => $payload['bill'],
                            "fee" => 2500,
                            "amount" => $payload['bill'] + 2500,
                            "trxid" => $payload['trxid'],
                            "remarks" => $payload['remarks'],
                            "refnum" => $payload['refnum'],
                            "email" => $payload['email'],
                            "phone" => $payload['phone'],
                            "customer_name" => $payload['customer_name'],
                        ],
                        "requestData" => $payload
                    ], 200);
                },
            ]);
            //end fake
        }
        Log::info('DD Req Inquiry', $payload);
        unset($payload["card_id"]);
        $res = self::service('POST','/directdebit/inquiry', $payload);
        Log::info('DD Resp inquiry', $res->json());
        return $res;
    }

    public static function paymentDD($payload)
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/directdebit/payment' => function () use ($payload) {
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "success",
                        "responseData" => [
                            "sof_code" => $payload['sof_code'],
                            "bill" => $payload['bill'],
                            "fee" => $payload['fee'],
                            "amount" => $payload['bill'] + 2500,
                            "trxid" => $payload['trxid'],
                            "remarks" => $payload['remarks'],
                            "refnum" => $payload['refnum'],
                            "pay_refnum" => "88888" . rand(1000000, 9999999),
                            "email" => $payload['email'],
                            "phone" => $payload['phone'],
                            "customer_name" => $payload['customer_name'],
                        ],
                        "requestData" => $payload
                    ], 200);
                },
            ]);
            //end fake
        }

        unset($payload["card_id"]);
        Log::info('DD Payment Request', $payload);
        $res = self::service('POST','/directdebit/payment', $payload);
        Log::info('DD payment Response', $res->json());
        return $res;
    }

    public static function statusDD($payload)
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/directdebit/payment' => function () use ($payload) {
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "success",
                        "responseData" => [
                            "sof_code" => $payload['sof_code'],
                            "bill" => $payload['bill'],
                            "fee" => $payload['fee'],
                            "amount" => $payload['bill'] + 2500,
                            "trxid" => $payload['trxid'],
                            "remarks" => $payload['remarks'],
                            "refnum" => $payload['refnum'],
                            "pay_refnum" => "88888" . rand(1000000, 9999999),
                            "email" => $payload['email'],
                            "phone" => $payload['phone'],
                            "customer_name" => $payload['customer_name'],
                        ],
                        "requestData" => $payload
                    ], 200);
                },
            ]);
            //end fake
        }
        unset($payload["card_id"]);
        Log::info('DD Status Request', $payload);
        $res = self::service('POST','/directdebit/advice', $payload);
        Log::info('DD Status Response', $res->json());
        return $res;
    }

    public static function cardList($payload)
    {
        $res = self::service('POST','/sof/cardlist', $payload);
        Log::info('Card list', $res->json());
        return $res;
    }

    public static function sofList()
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/sof/list' => function () {
                    return Http::response([
                        "status" => "success",
                        "rc" => "0000",
                        "rcm" => "success",
                        "responseData" => [
                            [
                                "sof_id" => 4,
                                "code" => "BNI",
                                "name" => "Bank Negara Indonesia",
                                "description" => "BNI",
                                "payment_method_id" => 2,
                                "payment_method_code" => "VA"
                            ],
                            [
                                "sof_id" => 3,
                                "code" => "MANDIRI",
                                "name" => "Bank Mandiri",
                                "description" => "Bank Mandiri",
                                "payment_method_id" => 2,
                                "payment_method_code" => "VA"
                            ],
                            [
                                "sof_id" => 3,
                                "code" => "MANDIRI",
                                "name" => "Bank Mandiri",
                                "description" => "Bank Mandiri",
                                "payment_method_id" => 1,
                                "payment_method_code" => "DD"
                            ],
                            [
                                "sof_id" => 254,
                                "code" => "BRI",
                                "name" => "PT Bank Rakyat Indonesia Tbk - Prod",
                                "description" => "PT Bank Rakyat Indonesia Tbk-Prod",
                                "payment_method_id" => 2,
                                "payment_method_code" => "VA"
                            ],
                            [
                                "sof_id" => 254,
                                "code" => "BRI",
                                "name" => "PT Bank Rakyat Indonesia Tbk - Prod",
                                "description" => "PT Bank Rakyat Indonesia Tbk-Prod",
                                "payment_method_id" => 1,
                                "payment_method_code" => "DD"
                            ]
                        ]
                    ], 200);
                },
            ]);
            //end fake
        }

        $res = self::service('POST','/sof/list',[]);
        Log::info('SOF list', $res->json());
        return $res;
    }

    public static function listSubMerchant()
    {
        $res = self::service('GET','/merchant-data/submerchant',[]);
        return $res;
    }
}
