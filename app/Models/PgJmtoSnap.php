<?php

namespace App\Models;

use App\Models\Dto\BindingDto;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use ParagonIE\ConstantTime\Base64;
use phpDocumentor\Reflection\DocBlock\Tags\Throws;
use Illuminate\Support\Str;

class PgJmtoSnap extends Model
{

    public static function getToken()
    {
        $token = Redis::get('token_snap_pg');
        if (!$token) {
            $now = Carbon::now();
            $hours = Carbon::now()->addMinute(59);
            $diff = $now->diffInMinutes($hours) * 60;
            $token = self::generateToken()['access_token'] ?? '';
            if ($token == '') {
                // throw new Exception("token not found",422);
            }
            if (env('PG_FAKE_RESPON') != true) {
                Redis::set('token_snap_pg', $token);
                Redis::expire('token_snap_pg', $diff);
            }
        }
        return $token;
    }

    public static function generateToken()
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/oauth/token' => function () {
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
        $timestamp = Carbon::now()->format('c');
        $client_id = env('PG_CLIENT_ID');
        $payload = $client_id . '|' . $timestamp;
        $signature = self::generateSignatureToken($timestamp, $client_id, $payload);
        $body = array(
            'grantType' => 'client_credentials',
            'additionalInfo' => array()
        );

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode(env('PG_CLIENT_ID') . ':' . env('PG_CLIENT_SECRET')),
            'Content-Type' => 'application/json',
            'X-CLIENT-KEY' => $client_id,
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signature
        ])
        ->withoutVerifying()
        ->post(env('PG_BASE_URL_SNAP') . '/oauth/token', ['grant_type' => 'client_credentials', 'additionalInfo' => array()]);
        clock()->event("oauth token")->end();
        return $response->json();
    }

    public static function generateSignatureSnap($method, $path, $token, $payload, $timestamp)
    {
        if ($method == 'GET') {
            $payload = '';
        }
        $has_body = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
        $BodyHash = preg_replace('/\s+/', '', $has_body);
        $data = $method . ':' . $path . ':' . $token . ':' . $BodyHash . ':' . $timestamp;
        $secret_key = env('PG_CLIENT_SECRET');
        $sign = base64_encode(hash_hmac('sha512', $data, $secret_key, true));
        return [$sign, $timestamp, $BodyHash];
    }

    public static function generateSignatureToken($timestamp, $client_id, $payload)
    {
        if(env('PG_FAKE_RESPON') == true){
            return 'signature-fake';
        }
        
        $privateKey = env('PG_PRIVATE_KEY');
        $publicKey = env('PG_PUBLIC_KEY');
        openssl_sign($payload, $signature, $privateKey, 'sha256WithRSAEncryption');
        $sign = Base64::encode($signature);
        return $sign;

    }

    public static function service($method, $path, $payload)
    {
        $token = self::getToken();
        $timestamp = Carbon::now()->format('c');
        $signature = self::generateSignatureSnap($method, $path, $token, $payload, $timestamp);
        switch ($method) {
            case 'POST':
                clock()->event("pg{$path}")->color('purple')->begin();
                try {
                    $response = Http::withHeaders([
                        'Content-Type' => 'Application/json',
                        'Authorization' => 'Bearer ' . $token,
                        'X-TIMESTAMP' => $signature[1],
                        'X-SIGNATURE' => $signature[0],
                        'ORIGIN' => env('ORIGIN'),
                        'X-PARTNER-ID' => env('XPARTNERID'),
                        'X-EXTERNAL-ID' => (string) rand(10000000000000000, 99999999999999999),
                        'X-IP-ADDRESS' => env('XIPADDRESS'),
                        'X-DEVICE-ID' => env('PG_DEVICE_ID', '123456789'),
                        'X-LATITUDE' => env('XLATITUDE'),
                        'X-LONGITUDE' => env('XLONGITUDE'),
                        'CHANNEL-ID' => env('CHANNELID'),
                    ])
                        // ->withBody(json_encode($payload), 'Application/json')
                        ->timeout(10)
                        ->retry(1, 100)
                        ->withoutVerifying()
                        ->post(env('PG_BASE_URL_SNAP') . $path, $payload);
                    clock()->event("pg{$path}")->end();

                    return $response;
                } catch (\Exception $e) {
                }

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
                    ->timeout(10)
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->get(env('PG_BASE_URL') . $path, $payload);

                return $response;

            default:
                # code...
                break;
        }
    }

    public static function vaCreate($sof_code, $bill_id, $bill_name, $amount, $desc, $phone, $email, $customer_name, $sub_merchant_id, $prefix, $data)
    {

        if ($sof_code === 'MANDIRI') {
            $partnerServiceId = '89080';
            if ($data->order_type == 'ORDER_TRAVOY'){
                $virtualNumber = $prefix.env('PREFIX_KIOS').rand(10000, 99999);
            }
            else {
                $vacode = $data->tenant->prefix_va ?? '000';
                $virtualNumber = $prefix.$vacode.rand(10000, 99999);
            }
        }
        if ($sof_code === 'BRI') {
            $partnerServiceId = '77777031';
             //031 ganti jadi id merchant/submerchant
            $virtualNumber = rand(10000000, 99999999);

        }
        if ($sof_code === 'BNI') {
            $partnerServiceId = '98820861';
            if ($data->order_type == 'ORDER_TRAVOY'){
                $virtualNumber = env('PREFIX_KIOS').rand(10000, 99999);
            }
            else {
                $vacode = $data->tenant->prefix_va ?? '005';
                $virtualNumber = $vacode.rand(10000, 99999);
            }

        }

        $trx_id = 'Travoy'. Str::random(25);

        $payload = [
            "customerNo" => (string) $virtualNumber,
            "partnerServiceId" => $partnerServiceId,
            "virtualAccountNo" => $partnerServiceId . $virtualNumber,
            "virtualAccountName" => $customer_name ?? 'TRAVOY',
            "virtualAccountEmail" => $email,
            "virtualAccountPhone" => $phone,
            "totalAmount" => ["value" => $amount . ".00", "currency" => "IDR"],
            "billDetails" => [["billName" => $bill_name]],
            "virtualAccountTrxType" => "O",
            "expiredDate" => Carbon::now()->addMinutes(10)->format('c'),
            "trxId" => $trx_id,
            "additionalInfo" => ["description" => ($bill_id . '-' . $desc . '-' . $amount), "submerch_id" => $sub_merchant_id],
        ];

        
        if (env('PG_FAKE_RESPON') === true) {
            $fake = [
                "responseCode"=> "2002700",
                "responseMessage"=> "Success",
                "virtualAccountData"=> [
                    ...$payload,
                ]
            ];

            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/transfer-va/create-va' => function () use ($fake) {
                    return Http::response($fake, 200);
                }
            ]);
        }
        $res = self::service('POST', '/snap/merchant/v1.0/transfer-va/create-va', $payload)->json();

        Log::info(['Payload PG =>', $payload, 'Va Create => ', $res ?? 'ERROR']);

        if(($res['responseCode'] ?? null) == 2002700){
            //remove nanti kalau dari pg sudah d fix padding respon virtual accountNo
            // $res['virtualAccountData']['virtualAccountNo'] = '51105031456789';
            $res = [
                "status" => "success",
                "rc" => "0000",
                "rcm" => "success",
                "responseData" => [
                    "sof_code" => $sof_code,
                    "va_number" => str_replace(' ', '',$res['virtualAccountData']['virtualAccountNo']),
                    // "va_number" => str_replace(' ', '', $res['virtualAccountData']['partnerServiceId'].$res['virtualAccountData']['virtualAccountNo']),
                    "bill" => (string)((int) $res['virtualAccountData']['totalAmount']['value']),
                    "bill_id" => $bill_id,
                    "bill_name" => $bill_name,
                    "exp_date" =>  Carbon::parse($res['virtualAccountData']['expiredDate'])->isoFormat('dddd, D MMMM YYYY, H:mm:ss'),
                    "phone" => $phone,
                    "email" => $email,
                    "customer_name" => $customer_name,
                    "amount" => (string)((int) $res['virtualAccountData']['totalAmount']['value']),
                    "fee" => (string)((int) $res['virtualAccountData']['totalAmount']['value'] - $amount),
                    "responseCode" => "00",
                    "responseMessage" => "Success",
                    "desc" => $res['virtualAccountData']['additionalInfo']['description'],
                    "responseSnap" => $res
                ],
            ];
        }

        return $res;
    }

    public static function vaStatus($payload)
    {
        
        // try{
        //     $payload['virtualAccountNo'] =  $payload['partnerServiceId'].$payload['customerNo'];
        // }
        // catch (\Throwable $th) {        
        //     log::error([$payload,'format va salah']);
        // }
        $payload = Arr::only($payload,[
            "partnerServiceId",
            "customerNo",
            "virtualAccountNo",
            "additionalInfo",
            "inquiryRequestId",
            "virtualAccountEmail",
            "virtualAccountName",
            "virtualAccountPhone",
            "trxId",
        ]);
        $payload['inquiryRequestId'] = $payload['trxId'];

        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            $fake_respon_status_va = [
                "responseCode" => "2002700",
                "responseMessage" => "Success",
                "virtualAccountData" => [
                    ...$payload,
                    "totalAmount" => [
                        "value" => "0.00",
                        "currency" => "IDR"
                    ],
                    "billDetails" => [
                        [
                            "billName" => ""
                        ]
                    ],
                    "virtualAccountTrxType" => null,
                    "expiredDate" => "",
                    "trxId" => null,
                    "inquiryRequestId" => "VE123456789000001",
                    "paymentRequestId" => "",
                    "flagAdvise" => "0",
                    "paymentFlagStatus" => "1",
                ]
            ];
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/transfer-va/status' => function () use ($fake_respon_status_va) {
                    return Http::response($fake_respon_status_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('POST', '/snap/merchant/v1.0/transfer-va/status', $payload);
        Log::info(['Payload PG =>', $payload, 'Va status => ', $res->json() ?? 'ERROR']);
        return $res->json();
    }

    public static function vaBriDelete($sof_code, $bill_id, $va_number, $refnum, $phone, $email, $customer_name)
    {
        $payload = [
            "sof_code" => $sof_code,
            "bill_id" => $bill_id,
            "va_number" => $va_number,
            "refnum" => $refnum,
            "phone" => $phone,
            "email" => $email,
            "customer_name" => $customer_name,
        ];
        $res = self::service('POST', '/va/delete', $payload);
        Log::info('Va delete', $res->json());

        return $res->json();
    }

    public static function tarifFee($sof_id, $payment_method_id, $sub_merchant_id, $bill_amount)
    {
        $payload = [
            "sof_id" => $sof_id,
            "payment_method_id" => $payment_method_id,
            "sub_merchant_id" => $sub_merchant_id,
            "bill_amount" => $bill_amount,
        ];

        try {
            $res = self::service('POST', '/sof/tariffee', $payload);
            // Log::warning($res);
            // if ($res['status'] == 400) {
            //     // return $res['responseData'];
            //     $res = $res['responseData'];
            //     Log::warning('Trace PG Tarif Fee', $res);

            //     return $res;
            // }
            if ($res->successful()) {
                if ($res->json()['status'] == 'ERROR') {
                    Log::warning('PG Tarif Fee', $res->json());
                    return null;
                }
                // Log::error('Success Trace PG Tarif Fee', $res->json()['responseData']);
                return $res->json()['responseData'];
            }
        } catch (\Throwable $th) {

            $fake_respo_create_bad = [
                "status" => 400,
                "responseData" => [
                    "is_presentage" => null,
                    "value" => null
                ]
            ];
            $res = $fake_respo_create_bad['responseData'];
            // Log::error('Catch Trace PG Tarif Fee', $res);
            return $res;
        }


        // return null;
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
            "custom_field_1" => "test",
            "custom_field_2" => "",
            "custom_field_3" => "",
            "custom_field_4" => "",
            "custom_field_5" => ""
        ];
        $res = self::service('POST', '/sof/bind', $payload);
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
        $res = self::service('POST', '/sof/bind-validate', $payload);
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
        $res = self::service('POST', '/sof/unbind', $payload);
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
        $res = self::service('POST', '/directdebit/inquiry', $payload);
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
        $res = self::service('POST', '/directdebit/payment', $payload);
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
        $res = self::service('POST', '/directdebit/advice', $payload);
        Log::info('DD Status Response', $res->json());
        return $res;
    }

    public static function cardList($payload)
    {
        $res = self::service('POST', '/sof/cardlist', $payload);
        Log::info('Card list', $res->json() ?? 'ERROR');
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

        $res = self::service('POST', '/sof/list', []);
        Log::info('SOF list', $res->json() ?? 'ERROR');
        return $res;
    }

    public static function listSubMerchant()
    {
        $res = self::service('GET', '/merchant-data/submerchant', []);
        return $res;
    }
}