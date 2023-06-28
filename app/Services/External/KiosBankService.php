<?php

namespace App\Services\External;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\TransOrder;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


class KiosBankService
{
    protected $baseUrl;
    protected $accountKiosBank;
    protected $operatorPulsa;
    protected $username;
    protected $password;

    protected const SIGN_ON = '/auth/Sign-On';
    protected const ACTIVE_PRODUCT = '/Services/get-Active-Product';
    protected const PULSA_PRABAYAR = '/Services/getPulsa-Prabayar';
    protected const SINGLE_PAYMENT = '/Services/SinglePayment';
    protected const DUAL_PAYMENT = '/Services/Payment';

    protected const CEK_STATUS = '/Services/Check-Status';
    protected const CEK_DEPOSIT = '/Services/getCurrentDeposit';
    public const INQUIRY = '/Services/Inquiry';
    public const REINQUIRY = '/Services/Inquiry';


    function __construct()
    {
        $this->username = env('KIOSBANK_USERNAME');
        $this->password = env('KIOSBANK_PASSWORD');
        $this->baseUrl = env('KIOSBANK_URL');
        $this->accountKiosBank = [
            'mitra' => env('KIOSBANK_MITRA'),
            'accountID' => env('KIOSBANK_ACCOUNT_ID'),
            'merchantID' => env('KIOSBANK_MERCHANT_ID'),
            'merchantName' => env('KIOSBANK_MERCHANT_NAME'),
            'counterID' => env('KIOSBANK_COUNTER_ID')
        ];
        $this->operatorPulsa = [
            [
                'prefix_id' => '11',
                'name' => 'Indosat',
            ],
            [
                'prefix_id' => '21',
                'name' => 'Telkomsel',
            ],
            [
                'prefix_id' => '31',
                'name' => 'XL',
            ],
            [
                'prefix_id' => '41',
                'name' => 'Tri',
            ],
            [
                'prefix_id' => '51',
                'name' => 'Axis',
            ],
            [
                'prefix_id' => '81',
                'name' => 'Smartfren',
            ],
        ];
    }

    function generateDigest($method, $path)
    {
        $method = 'POST';
        $digest = Http::kiosbank()->post($path)->header('WWW-Authenticate');
        $auth_arr = explode(',', $digest);
        $params = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $params[$key] = substr($val, 1, strlen($val) - 2);
        }
        /*
        SESUAIKAN INI
        */
        $nc = 1;
        $cnonce = uniqid();

        $a1 = md5($this->username . ':' . $params['Digest realm'] . ':' . $this->password);
        $a2 = md5($method . ':' . $path);
        $response = md5($a1 . ':' . $params['nonce'] . ':' . $nc . ':' . $cnonce . ':' . $params['qop'] . ':' . $a2);
        $query = array(
            'username' => $this->username,
            'password' => $this->password,
            'realm' => $params['Digest realm'],
            'nonce' => $params['nonce'],
            'uri' => $path,
            'qop' => $params['qop'],
            'nc' => $nc,
            'cnonce' => $cnonce,
            'opaque' => $params['opaque'],
            'response' => $response
        );
        $query_str = 'username="' . $query['username'] . '",realm="' . $query['realm'] . '",nonce="' . $query['nonce'] . '",uri="' . $query['uri'] . '",qop="' . $query['qop'] . '",nc="' . $query['nc'] . '",cnonce="' . $query['cnonce'] . '",response="' . $query['response'] . '",opaque="' . $query['opaque'] . '"';
        return $query_str;
    }

    function http($method, $path, $payload = [])
    {
        $digest = $this->generateDigest(method: $method, path: $path);
        clock()->event("kiosbank {$path}")->color('purple')->begin();
        $http = Http::kiosbank()->withHeaders(['Authorization' => 'Digest ' . $digest]);
        switch ($method) {
            case 'POST':
                $http = $http->post($path, $payload);
                clock()->event("kiosbank {$path}")->end();

                break;

            case 'GET':
                $http = $http->get($path, $payload);
                clock()->event("kiosbank {$path}")->end();

                break;

            default:
                throw new Exception("Error Processing Request", 1);
                break;
        }

        Log::info([
            'method' => $method,
            'path' => $path,
            'payload' => $payload,
            'respons' => $http->json(),
            // 'start' => $current_date_time,
            // 'end' => $current_date_times
        ]);
        return $http;
    }

    public function signOn(): string
    {
        $res_json = $this->http('POST', self::SIGN_ON, $this->accountKiosBank);
        $res_json = $res_json->json();
        return $res_json['SessionID'];
    }

    public function getSeesionId()
    {
        $session = Redis::get('session_kios_bank');
        if (!$session) {
            $now = Carbon::now();
            $tomorrow = Carbon::tomorrow()->setMinute(15);
            $diff = $now->diffInMinutes($tomorrow) * 60;
            $session = $this->signOn();
            Redis::set('session_kios_bank', $session);
            Redis::expire('session_kios_bank', $diff);
        }

        return $session;
    }

    public function cekStatusProduct()
    {
        $payload = [
            'sessionID' => $this->getSeesionId(),
            ...$this->accountKiosBank
        ];
        $res_json = $this->http('POST', self::ACTIVE_PRODUCT, $payload)->json();
        return $res_json;
    }

    public function getSubKategoriProduct(){
        return ProductKiosBank::select('sub_kategori')->distinct()->get();
    }

    public function getProduct($kategori_pulsa = null, $kategori = null, $sub_kategori = null)
    {
        $product = $this->cekStatusProduct();
        $status_respon = $product['rc'] ?? '';
        if ($status_respon == '00') {
            if ($kategori && $sub_kategori) {
                $data = ProductKiosBank::where('kategori', strtoupper($kategori))
                    ->where('sub_kategori', ucwords($sub_kategori))
                    ->where('is_active', 1)
                    ->orderBy('kode', 'asc')
                    ->get();
            } else if ($kategori) {
                $data = ProductKiosBank::where('kategori', strtoupper($kategori))
                    ->where('is_active', 1)
                    ->orderBy('kode', 'asc')
                    ->get();

            } else if ($sub_kategori) {
                $data = ProductKiosBank::when($sub_kategori, function ($q) use ($sub_kategori) {
                    return $q->where('sub_kategori', $sub_kategori);
                })->where('is_active', 1)
                    ->orderBy('kode', 'asc')
                    ->get();
            } else {
                $data = ProductKiosBank::when($kategori_pulsa, function ($q) use ($kategori_pulsa) {
                    return $q->where('kategori', $kategori_pulsa);
                })->where('is_active', 1)
                    // ->orderBy('name', 'asc')
                    ->orderBy('kode', 'asc')
                    ->get();
            }


            $active = $product['active'];
            $active = explode(',', $active);
            foreach ($data as $key => $value) {
                $value->status = false;
            }

            if (count($active) > 1) {
                foreach ($active as $key => $value) {
                    foreach ($data as $k => $v) {
                        if ($value == $v->kode) {
                            $v->status = true;
                        }
                    }
                }

                // foreach ($data as $x => $z){
                //     if($z->status == false){
                //         unset($data[$x]);
                //     }

                // }
            }

            return $data->groupBy('sub_kategori');
        } else {
            return $product;
        }
    }

    public function showProduct($id)
    {
        $product =
            ProductKiosBank::where('id', $id)
                ->where('is_active', 1);
        // ProductKiosBank::findOrFail($id);



        return $product;
    }

    public function getListOperatorPulsa()
    {
        return $this->operatorPulsa;
    }

    public function listProductOperatorPulsa($prefix_id)
    {
        $payload = [
            'sessionID' => $this->getSeesionId(),
            'prefixID' => $prefix_id,
            'merchantID' => env('KIOSBANK_MERCHANT_ID')
        ];
        $res_json = $this->http('POST', self::PULSA_PRABAYAR, $payload)->json();
        return $res_json;
    }

    public function showProductPulsa($id)
    {
        $product_pulsa = ProductKiosBank::where('id', $id)
            ->where('sub_kategori', 'PULSA')
            ->where('is_active', 1)
            ->whereNotNull('prefix_id')->first();
        return $product_pulsa;
    }

    public function orderPulsa($data, $harga_kios, $harga_final)
    {
        $disc = 0;
        $order = new TransOrder();
        $order->order_type = TransOrder::ORDER_TRAVOY;
        $order->order_id = $data['code'] . '-' . $data['phone'] . '-' . rand(900000000000, 999999999999) . '-' . Carbon::now()->timestamp;
        $order->rest_area_id = 0;
        $order->tenant_id = 0;
        $order->business_id = 0;
        $order->customer_id = $data['customer_id'];
        $order->customer_name = $data['customer_name'];
        $order->customer_phone = $data['customer_phone'];
        $order->merchant_id = '';
        $order->sub_merchant_id = env('MERCHANT_KIOS', '');
        $order->sub_total = $harga_final - $disc;
        $order->status = TransOrder::WAITING_PAYMENT;
        $order->fee = env('PLATFORM_FEE');
        $order->total = $order->sub_total + $order->fee;
        $order->description = 'single';
        $order->harga_kios = $harga_kios;
        $order->discount = $disc;

        $harga_jual_kios = ProductKiosBank::where('kode', $data['code'])->first();
        $order->margin = $harga_jual_kios?->harga + ($harga_jual_kios?->fee_admin_bank ?? 0);
        $order->net_margin =  $order->margin - $disc;

        $order->save();
        // $order->sub_total = ($harga_jual_kios?->harga ?? 0) + ($res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan']) - $disc ;
        // $order->total = $order->sub_total + $order->fee;
      
       
        

        $request = [
            'referenceID' => '-',
            'data' => [
                'noHandphone' => $data['phone'],
                'harga_kios' => $harga_kios,
                'harga' => $harga_final
            ],
            'description' => 'INQUIRY'
        ];

        $order->log_kiosbank()->updateOrCreate([
            'trans_order_id' => $order->id
        ], [
                'data' => $request
            ]);

        //minta tambah updateOrCreate ke column inquiry

        return $order;
    }

    public function singlePayment($sub_total, $order_id, $harga_kios)
    {
        $order = explode('-', $order_id);
        $payload = [
            'total' => $harga_kios,
            'admin' => '000000000000',
            'tagihan' => $harga_kios,
            'sessionID' => $this->getSeesionId(),
            'productID' => $order[0] ?? '',
            'referenceID' => $order[2] ?? '',
            'merchantID' => env('KIOSBANK_MERCHANT_ID'),
            'customerID' => $order[1] ?? ''
        ];

        clock()->event('singlepayment kios')->color('purple')->begin();
        $res_json = $this->http('POST', self::SINGLE_PAYMENT, $payload)->json();
        clock()->event('singlepayment kios')->end();
        return $res_json;
    }

    public function dualPayment($sub_total, $order_id, $tagihan, $admin, $total)
    {

        $adminparam = ['753001', '753011', '753021', '753031', '753041', '753051', '753061', '753071', '753081', '753091', '100302'];

        $order = explode('-', $order_id);

        if (in_array($order[0], $adminparam)) {

            $payload = [
                'total' => sprintf("%012d", $total),
                'admin' => $admin,
                'tagihan' => sprintf("%012d", $total-$admin),
                'sessionID' => $this->getSeesionId(),
                'productID' => $order[0] ?? '',
                'referenceID' => $order[2] ?? '',
                'merchantID' => env('KIOSBANK_MERCHANT_ID'),
                'customerID' => $order[1] ?? ''
            ];

            // dd($payload);
        } else {
            $payload = [
                'total' => $total,
                'admin' => $admin,
                'tagihan' => $tagihan,
                'sessionID' => $this->getSeesionId(),
                'productID' => $order[0] ?? '',
                'referenceID' => $order[2] ?? '',
                'merchantID' => env('KIOSBANK_MERCHANT_ID'),
                'customerID' => $order[1] ?? ''
            ];

        }


        clock()->event('dualpayment kios')->color('purple')->begin();
        $res_json = $this->http('POST', self::DUAL_PAYMENT, $payload)->json();
        clock()->event('dualpayment kios')->end();

        return $res_json;
    }

    public function cekStatus($sub_total, $order_id, $admin, $harga_kios)
    {
        $order = explode('-', $order_id);
        $payload = [
            'total' => $harga_kios,
            'admin' => $admin,
            'tagihan' => $harga_kios,
            'sessionID' => $this->getSeesionId(),
            'productID' => $order[0],
            'referenceID' => $order[2],
            'merchantID' => env('KIOSBANK_MERCHANT_ID'),
            'customerID' => $order[1]
        ];

        clock()->event('cek status payment kios')->color('purple')->begin();
        $res_json = $this->http('POST', self::CEK_STATUS, $payload)->json();
        clock()->event('cek status kios')->end();

        return $res_json;
    }
    public function reinquiry($productId, $customerID, $referenceID)
    {

        $payload = [
            'sessionID' => $this->getSeesionId(),
            'merchantID' => env('KIOSBANK_MERCHANT_ID'),
            'productID' => $productId,
            'customerID' => $customerID,
            'referenceID' => $referenceID,
        ];
        clock()->event('re-inquiry kios')->color('purple')->begin();
        $res_json = $this->http('POST', self::INQUIRY, $payload);
        clock()->event('re-inquiry kios')->end();
        return $res_json;
    }

    public function callback($request)
    {
        try {
            $kode = $request['productID'];
            $customer = $request['customerID'];
            $referensi = $request['referenceID'];
            $id = $kode . '-' . $customer . '-' . $referensi;
            $data = TransOrder::where('order_id', 'LIKE', '%' . $id . '%')->first();

            if ($data) {
                DB::beginTransaction();
                Log::info(['callback', $request]);

                $request['description'] = $request['description'] ?? ($request['data']['status'] ?? '-');
                $request['data']['harga_kios'] = $request['data']['harga'];
                $request['data']['harga'] = $data->sub_total;

                if ($request['rc'] == '00') {
                    $data->status = TransOrder::DONE;
                    $data->save();
                }
                $data->log_kiosbank()->updateOrCreate([
                    'trans_order_id' => $data->id
                ], [
                        'data' => $request
                    ]);
                DB::commit();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::warning($th);
            return response()->json(['error' => (string) $th], 500);
        }
    }

    public function cekDeposit()
    {
        $payload = [
            'sessionID' => $this->getSeesionId(),
            ...$this->accountKiosBank
        ];
        $res_json = $this->http('POST', self::CEK_DEPOSIT, $payload);
        $res_json = $res_json->json();
        return $res_json;
    }

    public function uangelEktronik($data)
    {
        $order = new TransOrder();
        $order->order_type = TransOrder::ORDER_TRAVOY;
        $order->order_id = $data['code'] . '-' . $data['phone'] . '-' . rand(900000000000, 999999999999) . '-' . Carbon::now()->timestamp;
        $order->rest_area_id = 0;
        $order->tenant_id = 0;
        $order->business_id = 0;
        $order->customer_id = $data['customer_id'];
        $order->customer_name = $data['customer_name'];
        $order->customer_phone = $data['customer_phone'];
        $order->merchant_id = '';
        $order->sub_merchant_id = env('MERCHANT_KIOS', '');
        $order->fee = env('PLATFORM_FEE');
        $order->description = 'dual';
        $order->status = TransOrder::WAITING_PAYMENT;

        $ref = explode('-', $order->order_id);
        $payload = [
            'sessionID' => $this->getSeesionId(),
            'merchantID' => env('KIOSBANK_MERCHANT_ID'),
            'productID' => $data['code'],
            'customerID' => $data['phone'],
            'referenceID' => $ref[2],
        ];
        $current_date_time = Carbon::now()->toDateTimeString();
        // if ((substr($data['code'],  0, 3) == '753') && (strlen($data['phone']) != 16)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Nomor Kartu Anda Tidak Valid'
        //     ], 422);
        // }
        clock()->event('inquiry kios')->color('purple')->begin();

        try{
        $res_json = $this->http('POST', self::INQUIRY, $payload);
    } catch (\Throwable $th) {
        // dd($th instanceof \Exception);
        // throw new \Exception();
        return $th;
        }
        clock()->event('inquiry kios')->end();

        $res_json = $res_json->json();
        $current_date_times = Carbon::now()->toDateTimeString();
        Log::info(
            [
                'REQ' => $current_date_time,
                'RESP' => $current_date_times
            ]
        );

        if ($res_json['rc'] == '00') {
            if ($res_json['productID'] == '520021' || $res_json['productID'] == '520011') {
                $disc = 0;
                $order->harga_kios = $res_json['data']['total'];
              
                //harga jual

                $harga_jual_kios = ProductKiosBank::where('kode', $res_json['productID'])->first() ?? $res_json['data']['total'];
                $order->sub_total = ($harga_jual_kios?->harga ?? 0) + $res_json['data']['total'] - $disc;

                $order->margin = $harga_jual_kios?->harga + $harga_jual_kios?->fee_admin_bank ?? 0;
                $order->net_margin =  $order->margin - $disc;

                $order->total = $order->sub_total + $order->fee;

                $res_json['data']['harga_kios'] = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                $res_json['data']['harga'] = $order->sub_total;
                $res_json['description'] = 'INQUIRY';
                $res_json['status'] = 'INQUIRY';

                $order->save();
                $order->log_kiosbank()->updateOrCreate(['trans_order_id' => $order->id], [
                    'data' => $res_json
                ]);

                //minta tambah updateOrCreate ke column inquiry

                return $order;

            }
            else if ($res_json['productID'] == '100302') {
                $disc = 2500;
                $harga_jual_kios = ProductKiosBank::where('kode', $res_json['productID'])->first() ?? $res_json['data']['total'];
                $temp_harga = preg_replace('/[^0-9]/', '', $harga_jual_kios['name']) + $res_json['data']['AB'];
                
                $order->harga_kios = $temp_harga;
                $order->discount = $disc;
                $order->sub_total = $temp_harga - $disc ;

                $order->total = $order->sub_total + $order->fee;
                $order->margin = $harga_jual_kios->fee_admin_bank ?? $order->sub_total - $harga_jual_kios->harga;
                $order->net_margin = ($harga_jual_kios->fee_admin_bank ?? $order->sub_total - $harga_jual_kios->harga) - $disc;

                // $order->total = $order->sub_total + $order->fee;s

                $res_json['description'] = 'INQUIRY';
                $res_json['status'] = 'INQUIRY';
                $res_json['data']['harga_kios'] = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'] ?? $temp_harga;
                $res_json['data']['harga'] = $temp_harga;
                $res_json['data']['diskon'] = $disc;
                $res_json['diskon'] = $disc;

                $order->save();
                $order->log_kiosbank()->updateOrCreate(['trans_order_id' => $order->id], [
                    'data' => $res_json
                ]);

                //minta tambah updateOrCreate ke column inquiry

                return $order;

                



                // $order->total = $order->sub_total + $order->fee;

                // $res_json['data']['harga_kios'] = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                // $res_json['data']['harga'] = $order->sub_total;
                // $res_json['description'] = 'INQUIRY';
                // $res_json['status'] = 'INQUIRY';

                // $order->save();
                // $order->log_kiosbank()->updateOrCreate(['trans_order_id' => $order->id], [
                //     'data' => $res_json
                // ]);

                //minta tambah updateOrCreate ke column inquiry

            }
            
            else {
                $disc = 0;
                $order->harga_kios = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'] ?? $res_json;
                //harga jual
                $harga_jual_kios = ProductKiosBank::where('kode', $res_json['productID'])->first();
                
                // $order->sub_total = $harga_jual_kios?->harga ?? $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                $order->discount = $disc;
                $order->sub_total = ($harga_jual_kios?->harga ?? 0) + ($res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan']) - $disc ;
                $order->total = $order->sub_total + $order->fee;
              
                $order->margin = $harga_jual_kios?->harga + ($harga_jual_kios?->fee_admin_bank ?? 0);
                $order->net_margin =  $order->margin - $disc;

                $res_json['data']['harga_kios'] = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                $res_json['data']['harga'] = $order->sub_total;
                $res_json['data']['diskon'] = $disc;
                $res_json['diskon'] = $disc;
                $res_json['description'] = 'INQUIRY';
                $order->save();
                $order->log_kiosbank()->updateOrCreate(['trans_order_id' => $order->id], [
                    'data' => $res_json
                ]);

                //minta tambah updateOrCreate ke column inquiry

                return $order;
            }
        }

        return $res_json;
    }
    public function cek()
    {
        $cek = $this->cekDeposit();
        return $cek;

        // return Http::withOptions(["verify"=>false])
        //     ->withDigestAuth($this->username,$this->password)
        //     ->post($this->baseUrl.self::CEK_DEPOSIT)->json();
    }
}