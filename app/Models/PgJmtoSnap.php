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
            $result = self::generateToken();
            $token = $result['accessToken'] ?? '';
            $expire = $result['expiresIn'] ?? 0;
            if ($token == '') {
                // throw new Exception("token not found",422);
            }
            if (env('PG_FAKE_RESPON') != true) {
                Redis::set('token_snap_pg', $token);
                Redis::expire('token_snap_pg', $expire);
            }
        }
        return $token;
    }

    public static function generateToken()
    {
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/access-token/b2b' => function () {
                    return Http::response([
                        'accessToken' => 'ini-fake-access-token',
                        "tokenType" => "Bearer",
                        "expiresIn" => 36000,
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
        ->post(env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/access-token/b2b', ['grantType' => 'client_credentials', 'additionalInfo' => array()]);
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

        if($data->tenant?->prefix_va == NULL){
            $payload['additionalInfo'] = ["description" => ($bill_id . '-' . $desc . '-' . $amount)];
        }
         if($data->order_type == 'ORDER_TRAVOY'){
            $payload['additionalInfo'] =  ["description" => ($bill_id . '-' . $desc . '-' . $amount), "submerch_id" => $sub_merchant_id];
        }
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
        // Daftar kunci yang wajib ada dalam array
        $requiredKeys = [
            'bankCardNo', 
            'bankCardType', 
            'email', 
            'expiryDate', 
            'identificationNo', 
            'identificationType',
            'accountName',
            'phoneNo',
            'deviceId',
            'channel',
            'sofCode',
        ];

        // Memeriksa apakah semua kunci yang diperlukan ada di array
        foreach ($requiredKeys as $key) {
            if (!Arr::has($payload, $key)) {
                throw new Exception("Missing required key: " . $key);
            }
        }

        // Example
        // $cardData = [ //Encrypted using AES-256
        //     'bankCardNo' => '5221849061514792', //Max 19 Digit
        //     'bankCardType' => 'D', //D : Debit, C: Credit
        //     "email"=> "expired@mail.com",
        //     'expiryDate' => '0525', // MMYY (ex: 0524)
        //     'identificationNo' => '3434448790988882',
        //     'identificationType' => '02',
        // ];

        //ambil key tertentu
        $cardData = Arr::only(
            $payload,
            [
                'bankCardNo', 
                'bankCardType', 
                'email', 
                'expiryDate', 
                'identificationNo', 
                'identificationType'
            ]
        );

        // key
        $client_secret = env('PG_CLIENT_SECRET');
        $key = md5($client_secret);
        $iv = substr($key, 0, 16);

        $encryptedData = openssl_encrypt(
            data: json_encode($cardData),
            cipher_algo: 'aes-256-cbc',
            passphrase: substr($key, 0, 32),
            iv: $iv // Initialization vector (IV)
        );

        // Example
        // $payload = [
        //     'cardData' => $encryptedData,
        //     "accountName" => "yanto",
        //     "custIdMerchant" => "0012345679504",
        //     "phoneNo" => "628132063356",
        //     "additionalInfo" => [
        //         "deviceId" => "12345679237",
        //         "channel" => "mobilephone",
        //         "sofCode" => "BRI"
        //     ]
        // ];

        $param = [
            'cardData' => $encryptedData,
            "accountName" => $payload['accountName'],
            "custIdMerchant" => "0012345679504",
            "phoneNo" => $payload['phoneNo'],
            "additionalInfo" => [
                "deviceId" => $payload['deviceId'] ?? '12345679237',
                "channel" => $payload['channel'] ?? "mobilephone",
                "sofCode" => $payload['sofCode']
            ]
        ];

        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/registration-card-bind' => function () use ($payload) {
                    return Http::response([
                        "responseCode" => "2000100",
                        "responseMessage" => "Request has been processed successfully",
                        "referenceNo" => "BIND66e3e3fe54902",
                        "additionalInfo" => [
                            "sofCode" => $payload['sofCode'],
                            "bankCardNo" => $payload['bankCardNo'],
                            "phoneNo" => $payload['phoneNo'],
                            "email" => $payload['email'],
                            "accountName" => $payload['accountName'],
                        ]
                    ], 200);
                },
            ]);
            //end fake
        }

        $res = self::service('POST', '/snap/merchant/v1.0/registration-card-bind', $param);
        Log::info('DD bind', $res->json());
        return $res;
    }

    public static function bindValidateDD($payload)
    {
        // Example
        // $payload = [
        //     'originalReferenceNo'=> 'DD66d5692ab8b47',
        //     'type'=> 'card',
        //     'otp'=> '999999',
        //     'additionalInfo'=> [
        //         'deviceId'=> '12345679237',
        //         'channel'=> 'mobilephone',
        //         'sofCode'=> 'BRI',
        //     ]
        // ];

        $param = [
            'originalReferenceNo'=> $payload['originalReferenceNo'],
            'type'=> $payload['type'],
            'otp'=> $payload['otp'],
            'additionalInfo'=> [
                'deviceId'=> '12345679237',
                'channel'=> 'mobilephone',
                'sofCode'=> $payload['sofCode'],
                'bindId'=> $payload['bindId'] ?? '',
            ]
        ];

        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/otp-verification' => function () use ($payload) {
                    return Http::response([
                        "responseCode" => "2000400",
                        "responseMessage" => "Request has been processed successfully",
                        "referenceNo" => "BIND66e3e4e140694",
                        "bankCardToken" => "card_.eyJpYXQiOjE3MjYyMTE0MzIsImlzcyI6IkJhbmsgQlJJIC0gRENFIiwianRpIjoiMWVjN2M4YjEtMTIzZC00M2UzLWExODAtM2E5OTdlZDZhZWZlIiwicGFydG5lcklkIjoi77-9Iiwic2VydmljZU5hbWUiOiJERF9FWFRFUk5BTF9TRVJWSUNFIn0.P-MqW9pxWpXk6nuo0Yss7Punuc4G1tbK95hhlPnRP1uQEndQxXLGBYh0iKvYijDpLbSTNSZHI_va2s6ANdeJAuN4fTf86RfcCXeFVHFjKIzdhAMVzJxDf5cTBu1_mtLy7x-2HGAavfeUUcFnVX1vJ0QvjUO4oIDdzWtTDkQLJyUK2a_1UzNv7-Oldq79UhId9PHhzE7l0RIDBJ3YmfOqZYRX1W5aCYfqxzK4uCAQqM2Xguq98E6PDFaBfiANeZueS3WjUm3nMApsP77hW4ybmnqxAOq6rEgikIy7JEv0EjaqDQK0C9GGtksARbs7UyS0MU6bxhw3l9F3x8harZdOWw",
                        "additionalInfo" => [
                            "sofCode" => $payload['sofCode'],
                            "bankCardNo" => "5221849061514792",
                            "phoneNo" => "628132063356",
                            "email" => "expired@mail.com",
                            "accountName" => "yanto",
                            "bindId" => "9999"
                        ]
                    ], 200);
                },
            ]);
            //end fake
        }
        $res = self::service('POST', '/snap/merchant/v1.0/otp-verification', $param);
        Log::info('DD bind validate', $res->json());
        return $res;
    }

    public static function  unBindDD($payload)
    {
        // Sample Payload
        // "partnerReferenceNo" => "BIND66ecde9e61e9c",
        // "token" => "card_.eyJpYXQiOjE3MjY3OTk3MzQsImlzcyI6IkJhbmsgQlJJIC0gRENFIiwianRpIjoiZDk2YjM2MzYtMTBmZi00ZmMxLWFlZjItMzczMjZkMjQ1NzhlIiwicGFydG5lcklkIjoi77-9Iiwic2VydmljZU5hbWUiOiJERF9FWFRFUk5BTF9TRVJWSUNFIn0.an4jOHnX3I9kyk1ytINfX4cqnUmcQLewIbp8qOrOdRI6A5LO0XahinVDMYFDE1WJjRIyBvDVXEes3usFL484cEk3T9Dl-k4s07nqe-wlHc4d6xJUS4Ay3xkghuKw8gK_zZs9TfYcMc0ESCM-7cQpkRM4tipCqeuJniQtrOQM1MmULwy-0Aiv2YmGg_rMjMfegI0Y7XHp8PLT6gjXogBLE56H3xFbyd_dZXAYBzWbX0BPFBLbN1QtIYLEzXVgNGLUQdgzLACU6MIB_jG9rN2Qfq7FStD7sWi38jehc9OPnd1-BLoHO3u5iRkM3NxBuWlTaK-StINx3u_B7VZkXFshIg",
        // "additionalInfo" => [
        //     "deviceId" => "12345679237",
        //     "channel" => "mobilephone",
        //     "bindId" => "475",
        //     "sofCode" => "BRI"
        // ]
        $payload = [
            'partnerReferenceNo' => $payload['partnerReferenceNo'],
            'token' => $payload['token'],
            'additionalInfo'=> [
                'deviceId'=> '12345679237',
                'channel'=> 'mobilephone',
                'bindId'=> $payload['bindId'],
                'sofCode'=> $payload['sofCode']
            ]
        ];
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/registration-card-unbind' => function () use ($payload) {
                    return Http::response([
                        "responseCode" => "2000500",
                        "responseMessage" => "Request has been processed successfully",
                        "referenceNo" => "BIND66e3e4e140694",
                    ], 200);
                },
            ]);
            //end fake
        }
        $res = self::service('POST', '/snap/merchant/v1.0/registration-card-unbind', $payload);
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
        // Example
        // $payload = [
        //     'originalPartnerReferenceNo'=> '591802',
        //     'amount'=> [
        //         'currency'=> 'IDR',
        //         'value'=> '120000.00'
        //     ],
        //     'additionalInfo'=> [
        //         'deviceId'=> '12345679237',
        //         'channel'=> 'mobilephone',
        //         'bindId'=> '468',
        //         'accountName'=> 'yanto',
        //         'phoneNo'=> '628132063356',
        //         'email'=> 'expired@mail.com',
        //         'sofCode'=> 'BRI',
        //         'remarks'=> 'bayar bakso hehe'
        //     ]
        // ];

        $param = [
            'originalPartnerReferenceNo'=> '591802',
            'amount'=> [
                'currency'=> 'IDR',
                'value'=> $payload['amount']
            ],
            'additionalInfo'=> [
                'deviceId'=> '12345679237',
                'channel'=> 'mobilephone',
                'bindId'=> $payload['bindId'],
                'accountName'=> $payload['accountName'],
                'phoneNo'=> $payload['phoneNo'],
                'email'=> $payload['email'],
                'sofCode'=> $payload['sofCode'],
                'remarks'=> $payload['remarks']
            ]
        ];
        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL_SNAP') . '/snap/merchant/v1.0/debit/payment-host-to-host' => function () use ($payload) {
                    return Http::response([
                        "responseCode" => "2005400",
                        "responseMessage" => "Request has been processed successfully",
                        "referenceNo" => "DD99999999999",
                        "additionalInfo" => [
                            "amount" => [
                                "value" => $payload['amount'],
                                "currency" => "IDR"
                            ],
                            "feeAmount" => [
                                "value" => "2400.00",
                                "currency" => "IDR"
                            ],
                            "totalAmount" => [
                                "value" => (string)((float) $payload['amount'] + 2400.00),
                                "currency" => "IDR"
                            ]
                        ]
                    ], 200);
                },
            ]);
            //end fake
        }

        Log::info('DD Payment Request', $param);
        $res = self::service('POST', '/snap/merchant/v1.0/debit/payment-host-to-host', $param);
        Log::info('DD payment Response', $res->json());
        return $res;
    }

    public static function statusDD($param)
    {
        // Example
        // $payload = [
        //      "originalReferenceNo"=> "DD66e1815995d46",
        //      "serviceCode"=> "55",
        //      "additionalInfo"=> [
        //          "bindId"=> "476",
        //          "sofCode"=> "BRI"
        //      ]
        // ];

        $payload = [
            'originalPartnerReferenceNo'=> '591801',
            'originalReferenceNo'=> $param['originalReferenceNo'],
            'serviceCode'=> '55',
            'additionalInfo'=> [
                'bindId'=> $param['bindId'],
                'sofCode'=> $param['sofCode'],
            ]
        ];


        if (env('PG_FAKE_RESPON') === true) {
            //for fake
            Http::fake([
                env('PG_BASE_URL') . '/snap/merchant/v1.0/debit/status' => function () use ($payload) {
                    return Http::response([
                        "responseCode"=> "2005500",
                        "responseMessage"=> "Request has been processed successfully",
                        "originalPartnerReferenceNo"=> "591801",
                        "originalReferenceNo"=> "DD66e1815995d46",
                        "serviceCode"=> "17",
                        "latestTransactionStatus"=> "00",
                        "transactionStatusDesc"=> "paid",
                        "originalResponseCode"=> "2005500",
                        "originalResponseMessage"=> "Request has been processed successfully",
                        "refundHistory"=> [],
                        "transAmount"=> [
                            "value"=> "120000.00",
                            "currency"=> "IDR"
                        ],
                        "paidTime"=> "2024-09-11 18=>40=>54",
                        "additionalInfo"=> []
                    ], 200);
                },
            ]);
            //end fake
        }

        Log::info('DD Status Request', $payload);
        $res = self::service('POST', '/snap/merchant/v1.0/debit/status', $payload);
        Log::info('DD Status Response', $res->json());
        return $res;
    }
}