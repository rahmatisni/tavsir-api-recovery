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

class LaJmto extends Model
{


    public static function generateSignature($request_body)
    {
        $request_body = json_encode($request_body);
        $cid = env('LA_CID');
        $secretkey = env('LA_SECRETKEY');

        // if ($method == 'GET') {
        //     $request_body = '';
        // }
        $signature = $cid . ':' . $request_body . ':' . $secretkey;
        $hmacSignature = hash_hmac('sha512', $signature, $secretkey);

        // dump($signature);
        // $privateKey = env('PG_PRIVATE_KEY');
        // $publicKey = env('PG_PUBLIC_KEY');
        // openssl_sign($data, $signature, $privateKey, 'sha256WithRSAEncryption');
        // $sign = Base64::encode($signature);
        // $verify = openssl_verify($data, $signature, $publicKey, 'sha256WithRSAEncryption');
        return $hmacSignature;
    }

    public static function service($method, $path, $payload)
    {
        $signature = self::generateSignature($payload);
        // dd($payload, $signature);

        
        switch ($method) {
            case 'POST':
                clock()->event("LA{$path}")->color('purple')->begin();
                $response = Http::withHeaders([
                    'Content-Type' => 'Application/json',
                    'cid' => env('LA_CID'),
                    'Signature' => $signature
                ])
                    ->timeout(10)
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->post(env('LA_BASE_URL') . $path, $payload);
                clock()->event("LA{$path}")->end();

                return $response;

            case 'GET':
                $response = Http::withHeaders([
                    'Content-Type' => 'Application/json',
                    'cid' => env('LA_CID'),
                    'Signature' => $signature
                ])
                    ->timeout(10)
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->get(env('LA_BASE_URL') . $path, $payload);
                clock()->event("LA{$path}")->end();

                return $response;

            default:
                # code...
                break;
        }

    }

    public static function refund($method, $path, $payload)
    {
        $signature = self::generateSignature($payload);
        // dd($payload, $signature);

        switch ($method) {
            case 'POST':
                clock()->event("LA{$path}")->color('purple')->begin();
                $response = Http::withHeaders([
                    'Content-Type' => 'Application/json',
                    'cid' => env('LA_CID'),
                    'Signature' => $signature,
                    'client-id' =>env('la_client_id'),
                    'x-api-key'=> env('x_api_key')
                ])
                    ->timeout(10)
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->post(env('LA_REFUND_URL') . $path, $payload);
                clock()->event("LA{$path}")->end();

                return $response;

            case 'GET':
                $response = Http::withHeaders([
                    'Content-Type' => 'Application/json',
                    'cid' => env('LA_CID'),
                    'Signature' => $signature,
                    'client-id' =>env('la_client_id'),
                    'x-api-key'=> env('x_api_key')
                ])
                    ->timeout(10)
                    ->retry(1, 100)
                    ->withoutVerifying()
                    ->get(env('LA_REFUND_URL') . $path, $payload);
                clock()->event("LA{$path}")->end();

                return $response;

            default:
                # code...
                break;
        }

    }

    public static function qrCreate($sof_code, $bill_id, $bill_name, $amount, $desc, $phone, $email, $customer_name, $sub_merchant_id)
    {
        // dd($bill_id);
        $parts = explode("-", $bill_id);

        // Get the last element
        $lastElement = end($parts);
        

        $payload = [
            "fee"  =>str_pad(env('PLATFORM_FEE'), 10, '0', STR_PAD_LEFT).'00',
            "amount" =>str_pad($amount, 10, '0', STR_PAD_LEFT).'00',
            "city" => "Jakarta",
            "postalCode" => "12190",
            "merchantName" => env('LA_MERCHANT_NAME'),
            "merchantID" => env('LA_MERCHANT_ID'),
            "merchantPan" => env('LA_MERCHANT_PAN'),
            "merchantCriteria" => "UME",
            "merchantTrxID" => str_replace('-', '', $lastElement),
            "partnerMerchantID" => "12345678910", //tenant merchdant i
        ];

        
        if (env('LA_FAKE_RESPON') === true) {
            

            // $fake_respo_create_va = [
            //     "status" => "success",
            //     "rc" => "0000",
            //     "rcm" => "success",
            //     "responseData" => [
            //         "sof_code" => $sof_code,
            //         "va_number" => "7777700100299999",
            //         "bill" => $payload['amount'],
            //         "fee" => "1000",
            //         "amount" => (string) $amount + 1000,
            //         "bill_id" => $payload['bill_id'],
            //         "bill_name" => $payload['bill_name'],
            //         "desc" => $payload['desc'],
            //         "exp_date" => $payload['exp_date'],
            //         "refnum" => "VA" . Carbon::now()->format('YmdHis'),
            //         "phone" => $payload['phone'],
            //         "email" => $payload['email'],
            //         "customer_name" => $payload['customer_name'],
            //     ],
            //     "requestData" => $payload
            // ];
            $fake_respo_create_va = [
                "status" => "success",
                "rc" => "0000",
                "msg" => "success",
                "responseData" => [
                    "sof_code" => $sof_code,
                    "va_number" => "7777700100299999",
                    "bill" => $amount,
                    "bill_id" => $bill_id,
                    "bill_name" => $bill_name,
                    "exp_date" => '',
                    "phone" => $phone,
                    "email" => $email,
                    "customer_name" => $customer_name,
                    "fee" => 0,
                    "responseCode" => "00",
                    "responseMessage" => "Success",
                    "qrString" => "00020101021226700017COM.TELKOMSEL.WWW0119936009110024567000002159203310116150010303UME520411115802ID5907Chatime6007Jakarta61051219062180114BEJO12345678905303360550202560320054031006304A1DE",
                    "merchantTrxID" => $bill_id,
                    "amount" => $amount,
                    "trxid" => $bill_id,
                    "desc" => $desc,
                    "opt_key" => "",
                    "opt_value" => "",
                    "LINK_URL" => "",
                    "refnum" => "",
                    "merchantName" => env('LA_MERCHANT_NAME'),
                    "nationalMerchantID" => "9183947593748374836"
                ]
            ];

            Http::fake([
                env('LA_BASE_URL') . '/qr/generate' => function () use ($fake_respo_create_va) {
                    return Http::response($fake_respo_create_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('POST', '/qr/generate', $payload)->json();
        Log::info([$payload, $res]);
        if($res['responseCode'] == 00){
        $response = [
            "status" => "success",
            "rc" => "0000",
            "msg" => "success",
            "responseData" => [
                "sof_code" => $sof_code,
                "bill_id" => $bill_id,
                "bill_name" => $bill_name,
                "phone" => $phone,
                "email" => $email,
                "exp_date" => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                "customer_name" => $customer_name,
                "fee" => 0,
                "responseCode" => $res['responseCode'],
                "responseMessage" => $res['responseMessage'],
                "qrName" => $payload['merchantName'],
                "qrString" =>  $res['qrString'],
                "merchantTrxID" => $res['merchantTrxID'],
                "amount" => $amount,
                "trxid" => $bill_id,
                "desc" => $desc,
                "opt_key" => "",
                "opt_value" => "",
                "LINK_URL" => "https://linkaja.id/applink/payment?data=8hAr3Yn8TsCQGSEINbjHA7cv5iTiHNcx90k31IjBJQTNQMLXVdW1Kc_yNmF3nLYWY6_aDrSwYgB9JeeiAJ1c0iunWIak6K_2Ojstgv8uv_lg5rTed5PUthNdXm-EcqMLO2aLeabCF-qjgH2yvbg7TnNZlmLkkYi8_nEfHmpAG_ffrbTrHw3wa4-LT61VwrLYR3e3-8VaOlDRbQpNU5Al5wxSt8SmCH24gOvmESiQRTF3a4OHdgKCrJTtn15Fqm0YYOdClKAeCUqC4k8qEg_xUdeeF54B2G9yF3rY1-Cook1TzFl5nl739mYBvCmXUVcgTPG0wqhxkCqQ98FO0X97p9AENIoV6zFZeyks6hsFWopwoyRo1ZcgfFfrg_j8zR2ZhVB9v2xKfYGFnj1SIUyvHYwuwrau5c8Qb-0-GWhEzlSeDBHxfTZJuw==",
                "refnum" => $res['merchantTrxID']
            ],
            "la_response" => $res

        ];
        Log::info([$payload, $response]);

        return $response;

        }
        else {
            $response = [
                "status" => "error",
                "rc" => $res['responseCode'],
                "msg" => $res['responseMessage']
            ];
            Log::info([$payload, $response]);

            return $response;
        }
        // Log::info('Va create res', $res);
        // return $response;
    }

    public static function qrStatus($bill_id)
    {
        $parts = explode("-", $bill_id);
        // Get the last element
        $lastElement = end($parts);
        $payload = [
            "merchantID" =>env('LA_MERCHANT_ID'),
            "merchantTrxID" =>$lastElement
        ];
        
        // $payload = [
        //     "merchantID" =>  '605111309311801',
        //     "merchantTrxID" => 'TEST7329893845'
        // ];

        if (env('LA_FAKE_RESPON') === true) {
            //for fake
            $fake_respon_status_va = [
                "status" => "success",
                "rc" => "0000",
                "msg" => "success",
                "responseData" => [
                    "pay_status" => "1",
                    "sof_code" => $sof_code,
                    "bill" => $amount,
                    "fee" => 0,
                    "amount" => $amount,
                    "trxid" => $bill_id,
                    "remarks" => "",
                    "refnum" => "",
                    "pay_refnum" => "",
                    "email" => $email,
                    "phone" => $phone,
                    "customer_name" => $customer_name,
                    "status" => "00",
                    "message" => "SUCCESS",
                    "data" => [
                        "bill_id" => $bill_id,
                        "trxId" => $bill_id,
                        "fromAccount" => "9360001430000034980",
                        "trxDate" => "0719185915",
                        // "amount" => $amount
                    ]
                ]
            ];
            Http::fake([
                env('LA_BASE_URL') . '/transaction/inform/status' => function () use ($fake_respon_status_va) {
                    return Http::response($fake_respon_status_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::service('POST', '/transaction/inform/status', $payload)->json();
        Log::info([$payload, $res]);
        // $res = [
        //     "status" => "success",
        //     "rc" => "0000",
        //     "msg" => "success",
        //     "responseData" => [
        //         "pay_status" => "1",
        //         "sof_code" => $sof_code,
        //         "bill" => $amount,
        //         "fee" => 0,
        //         "amount" => $amount,
        //         "trxid" => $bill_id,
        //         "remarks" => "",
        //         "refnum" => "",
        //         "pay_refnum" => "",
        //         "email" => $email,
        //         "phone" => $phone,
        //         "customer_name" => $customer_name,
        //         "status" => "00",
        //         "message" => "SUCCESS",
        //         "data" => [
        //             "bill_id" => $bill_id,
        //             "trxId" => $bill_id,
        //             "fromAccount" => "9360001430000034980",
        //             "trxDate" => "0719185915",
        //             // "amount" => $amount
        //         ]
        //     ]
        // ];

        if($res['status'] == 00){
            $response = [
                "status" => "success",
                "rc" => "0000",
                "msg" => "success",
                "responseData" => [
                    "pay_status" => '1',
                    "bill_id" => $bill_id,
                    "fee" => 0,
                    "responseMessage" => $res['message'],
                    "merchantTrxID" => $res['data']['trxId'],
                    "trxid" =>$res['data']['trxId'],
                    "fromAccount" =>$res['data']['fromAccount'],
                    "trxDate" =>  $res['data']['trxDate'],
                    "amount" => substr($res['data']['amount'], 0, -2),
                    "refnum" => $res['data']['trxId']
                ],
                "la_response" => $res
            ];
            Log::info([$payload, $response]);

            return $response;
    
            }
            else {
                $response = [
                    "status" => "error",
                    "rc" => $res['status'],
                    "msg" => $res['message'],
                    "responseData" => [
                        "pay_status" => "0"
                    ],
                    "la_response" => $res
                ];
                 
                Log::info([$payload, $response]);

                return $response;
            }
            // 


        // Log::info($payload);
        // // Log::info(['Payload PG =>', $payload, 'Va status => ', $res->json()]);
        // return $res;
    }



    public static function qrRefund($payload)
    {
        // $payload = [
        //     env('LA_MERCHANT_ID'),
        //     "merchantTrxID" =>str_replace('-', '', $bill_id)
        // ];

        
       

        if (env('LA_FAKE_RESPON') === true) {
            //for fake
            $fake_respon_status_va = [
                "status" => "success",
                "rc" => "0000",
                "msg" => "success",
                "responseData" => [
                    "pay_status" => "1",
                    "sof_code" => $sof_code,
                    "bill" => $amount,
                    "fee" => 0,
                    "amount" => $amount,
                    "trxid" => $bill_id,
                    "remarks" => "",
                    "refnum" => "",
                    "pay_refnum" => "",
                    "email" => $email,
                    "phone" => $phone,
                    "customer_name" => $customer_name,
                    "status" => "00",
                    "message" => "SUCCESS",
                    "data" => [
                        "bill_id" => $bill_id,
                        "trxId" => $bill_id,
                        "fromAccount" => "9360001430000034980",
                        "trxDate" => "0719185915",
                        // "amount" => $amount
                    ]
                ]
            ];
            Http::fake([
                env('LA_REFUND_URL') . '/transaction' => function () use ($fake_respon_status_va) {
                    return Http::response($fake_respon_status_va, 200);
                }
            ]);
            //end fake
        }

        $res = self::refund('POST', '/transactions', $payload)->json();
        Log::info([$payload, $res]);

        // $res = [
        //     "status" => "success",
        //     "rc" => "0000",
        //     "msg" => "success",
        //     "responseData" => [
        //         "pay_status" => "1",
        //         "sof_code" => $sof_code,
        //         "bill" => $amount,
        //         "fee" => 0,
        //         "amount" => $amount,
        //         "trxid" => $bill_id,
        //         "remarks" => "",
        //         "refnum" => "",
        //         "pay_refnum" => "",
        //         "email" => $email,
        //         "phone" => $phone,
        //         "customer_name" => $customer_name,
        //         "status" => "00",
        //         "message" => "SUCCESS",
        //         "data" => [
        //             "bill_id" => $bill_id,
        //             "trxId" => $bill_id,
        //             "fromAccount" => "9360001430000034980",
        //             "trxDate" => "0719185915",
        //             // "amount" => $amount
        //         ]
        //     ]
        // ];

        if($res['status'] == 00){
            $response = [
                "status" => "success",
                "rc" => "0000",
                "msg" => "success",
                // "responseData" => [
                //     "pay_status" => '1',
                //     "bill_id" => $res,
                //     "fee" => 0,
                //     "responseMessage" => $res['message'],
                //     "merchantTrxID" => $res['data']['trxId'],
                //     "trxid" =>$res['data']['trxId'],
                //     "fromAccount" =>$res['data']['fromAccount'],
                //     "trxDate" =>  $res['data']['trxDate'],
                //     "amount" => $res['data']['amount'],
                // ],
                "la_response" => $res

            ];
            Log::info([$payload, $response]);

            return $response;
    
            }
            else {
                $response = [
                    "status" => "error",
                    "rc" => $res['status'],
                    "msg" => $res['message'],
                ];
                 
                Log::info([$payload, $response]);

                return $response;
            }
            // 


        // Log::info($payload);
        // // Log::info(['Payload PG =>', $payload, 'Va status => ', $res->json()]);
        // return $res;
    }

    // 
    // 

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

        $res = self::service('POST', '/sof/list', []);
        Log::info('SOF list', $res->json());
        return $res;
    }

    public static function listSubMerchant()
    {
        $res = self::service('GET', '/merchant-data/submerchant', []);
        return $res;
    }
}