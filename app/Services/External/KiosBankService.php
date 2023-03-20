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
    protected $http;
    protected $username;
    protected $password;

    protected const SIGN_ON = '/auth/Sign-On';
    protected const ACTIVE_PRODUCT = '/Services/get-Active-Product';
    protected const PULSA_PRABAYAR = '/Services/getPulsa-Prabayar';
    protected const SINGLE_PAYMENT = '/Services/SinglePayment';
    protected const DUAL_PAYMENT = '/Services/Payment';

    protected const CEK_STATUS = '/Services/Check-Status';
    protected const CEK_DEPOSIT = '/Services/getCurrentDeposit';
    protected const INQUIRY = '/Services/Inquiry';

    function __construct()
    {
        $this->username = env('KIOSBANK_USERNAME');
        $this->password = env('KIOSBANK_PASSWORD');
        $this->baseUrl = env('KIOSBANK_URL');
        $this->http = Http::baseUrl($this->baseUrl)
                        ->withOptions(["verify"=>false]);
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

    function generateDigest($method = 'POST', $path)
    {
        $digest = $this->http->post($path)->header('WWW-Authenticate');
        Log::info($digest);

        $auth_arr = explode(',', $digest);
        $params = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $params[$key] = substr($val, 1, strlen($val) - 2);
        }
        /*
            SESUAIKAN INI
        */
        $nc = '1'; //berurutan 1,2,3..dst sesuai request
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

    function http($method, $path , $payload=[])
    {
        $digest = $this->generateDigest(method: $method, path: $path);
        $http = $this->http->withHeaders(['Authorization' => 'Digest '.$digest]);
        switch ($method) {
            case 'POST':
                $http = $http->post($path, $payload);
                break;

            case 'GET':
                $http = $http->get($path, $payload);
                break;
            
            default:
                throw new Exception("Error Processing Request", 1);
                break;
        }
        Log::info($http->json());
        return $http;
    }

    public function signOn() : string
    {   
        $res_json =  $this->http('POST',self::SIGN_ON,$this->accountKiosBank);
        $res_json = $res_json->json();
        return $res_json['SessionID'];
    }

    public function getSeesionId()
    {
        $session = Redis::get('session_kios_bank');
        if(!$session)
        {
            $now = Carbon::now();
            $tomorrow = Carbon::tomorrow()->setMinute(15);
            $diff = $now->diffInMinutes($tomorrow) * 60;
            $session = $this->signOn();
            Redis::set('session_kios_bank',$session);
            Redis::expire('session_kios_bank',$diff);
        }

        return $session;
    }

    public function cekStatusProduct()
    {
        $payload = [
            'sessionID'=> $this->getSeesionId(),
            ...$this->accountKiosBank
        ];
        $res_json =  $this->http('POST',self::ACTIVE_PRODUCT,$payload)->json();
        return $res_json;
    }

    public function getProduct($request)
    {
        $kategori_pulsa = codefikasiNomor($request->nomor_hp);
        $product = $this->cekStatusProduct();
        $status_respon = $product['rc'] ?? '';
        if($status_respon == '00')
        {
            $kategori_pulsa = 'INDOSAT';
            $data = ProductKiosBank::when($kategori_pulsa, function($q)use($kategori_pulsa){
                return $q->where('kategori',$kategori_pulsa);
            })
            ->get();

            $active = $product['active'];
            $active =  explode(',',$active);
            foreach ($data as $key => $value) {
                $value->status = false;
            }

            if(count($active) > 1)
            {
                foreach ($active as $key => $value) {
                    foreach ($data as $k => $v) {
                       if($value == $v->kode)
                       {
                         $v->status = true;
                       }
                    }
                }
            }
            return $data->groupBy('sub_kategori');
        }else{
            return $product;
        }
    }
    
    public function showProduct($id)
    {
        $product = ProductKiosBank::findOrFail($id);
       
        return $product;
    }

    public function getListOperatorPulsa()
    {
        return $this->operatorPulsa;
    }

    public function listProductOperatorPulsa($prefix_id)
    {
        $payload = [
            'sessionID'=> $this->getSeesionId(),
            'prefixID'=> $prefix_id,
            'merchantID' => env('KIOSBANK_MERCHANT_ID')
        ];
        $res_json =  $this->http('POST',self::PULSA_PRABAYAR,$payload)->json();
        return $res_json;
    }

    public function showProductPulsa($id)
    {
        $product_pulsa = ProductKiosBank::where('id',$id)
                                    ->where('sub_kategori','PULSA')
                                    ->whereNotNull('prefix_id')->first();
        return $product_pulsa;
    }

    public function orderPulsa($data)
{
        $order = new TransOrder();
        $order->order_type = TransOrder::ORDER_TRAVOY;
        $order->order_id = $data['code'].'-'.$data['phone'].'-'.rand(900000000000,999999999999).'-'.Carbon::now()->timestamp;
        $order->rest_area_id = 0;
        $order->tenant_id = 0;
        $order->business_id = 0;
        $order->customer_id = $data['customer_id'];
        $order->customer_name = $data['customer_name'];
        $order->customer_phone = $data['customer_phone'];
        $order->merchant_id = '';
        $order->sub_merchant_id = '';
        $order->sub_total = $data['price'];
        $order->status = TransOrder::WAITING_PAYMENT;
        $order->fee = env('PLATFORM_FEE');
        $order->total = $order->sub_total + $order->fee;
        $order->description = 'single';
        $order->save();

        $Postdata = TransOrder::where('order_id', $order->order_id)->first();
        $orders = explode('-', $order->order_id);
        $nom = $data['description'];
        $nom = preg_replace('/[^0-9]/', '', $nom);

        $request = [
            'referenceID'=>'-',
            'data' => [
                'noHandphone'=>$data['phone'],
                'harga'=>$data['price'],
                'nominalProduk'=>$nom,
            ],
            'description'=>'INQUIRY'
        ];

        $Postdata->log_kiosbank()->updateOrCreate([
            'trans_order_id' => $Postdata->id
        ],[
            'data' => $request
        ]);

        return $order;
    }

    public function singlePayment($sub_total,$order_id)
    {
        $order = explode('-', $order_id);
        $payload = [
            'total'=>$sub_total,
            'admin'=>'000000000000',
            'tagihan'=>$sub_total,
            'sessionID'=> $this->getSeesionId(),
            'productID'=>$order[0] ?? '',
            'referenceID'=>$order[2] ?? '',
            'merchantID'=>env('KIOSBANK_MERCHANT_ID'),
            'customerID'=>$order[1] ?? ''
        ];

        Log::info($payload);
        $res_json =  $this->http('POST',self::SINGLE_PAYMENT,$payload)->json();
        return $res_json;
    }

    public function dualPayment($sub_total,$order_id,$tagihan,$admin, $total)
    {
        $order = explode('-', $order_id);
        $payload = [
            'total'=>$total,
            'admin'=> $admin,
            'tagihan'=>$tagihan,
            'sessionID'=> $this->getSeesionId(),
            'productID'=>$order[0] ?? '',
            'referenceID'=>$order[2] ?? '',
            'merchantID'=>env('KIOSBANK_MERCHANT_ID'),
            'customerID'=>$order[1] ?? ''
        ];


        Log::info($payload);
        $res_json =  $this->http('POST',self::DUAL_PAYMENT,$payload)->json();
        return $res_json;
    }

    public function cekStatus($sub_total,$order_id,$admin)
    {
        $order = explode('-', $order_id);
        $payload = [
            'total'=>$sub_total,
            'admin'=>$admin,
            'tagihan'=>$sub_total,
            'sessionID'=> $this->getSeesionId(),
            'productID'=>$order[0],
            'referenceID'=>$order[2],
            'merchantID'=>env('KIOSBANK_MERCHANT_ID'),
            'customerID'=>$order[1]
        ];
        $res_json =  $this->http('POST',self::CEK_STATUS,$payload)->json();
        return $res_json;
    }

    public function callback($request)
    {
        try {
            $kode = $request['productID'];
            $customer = $request['customerID'];
            $referensi = $request['referenceID'];
            $id = $kode.'-'.$customer.'-'.$referensi;            
            $data = TransOrder::where('order_id','LIKE','%'.$id.'%')->first();
           
            if($data){
                DB::beginTransaction();
                Log::info($request);
                // $request['data']['idPelanggan'] = $request['data']['noHandphone'] ?? ($request['data']['idPelanggan'] ?? '-');
                // // $request['data']['noReferensi'] = $request['referenceID'] ?? ($request['data']['noReferensi'] ?? '-');
                // $request['data']['noReferensi'] = $request['data']['noReferensi'] ?? '-';

                // $request['data']['status'] = $request['data']['status'] ?? ($request['description'] ?? '-');
                // $request['data']['harga'] = $request['data']['harga'] ?? ($data->sub_total ?? '0');

                // $request['data']['nama'] = $request['data']['nama'] ?? ($request['data']['data']['nama'] ?? '-');
                // $request['data']['nominalProduk'] = $request['data']['nominalProduk'] ?? ($request['data']['data']['nominalProduk'] ?? ($data->sub_total ?? '0'));
                
                $request['description'] =  $request['description'] ?? ($request['data']['status'] ?? '-');

                if ($request['rc'] == '00'){
                    $data->status = TransOrder::DONE;
                    $data->save();
                }
                $data->log_kiosbank()->updateOrCreate([
                    'trans_order_id' => $data->id
                ],[
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
            'sessionID'=> $this->getSeesionId(),
            ...$this->accountKiosBank
        ];
        $res_json =  $this->http('POST',self::CEK_DEPOSIT,$payload);
        $res_json = $res_json->json();
        return $res_json;
    }

    public function uangelEktronik($data)
    {
        $order = new TransOrder();
        $order->order_type = TransOrder::ORDER_TRAVOY;
        $order->order_id = $data['code'].'-'.$data['phone'].'-'.rand(900000000000,999999999999).'-'.Carbon::now()->timestamp;
        $order->rest_area_id = 0;
        $order->tenant_id = 0;
        $order->business_id = 0;
        $order->customer_id = $data['customer_id'];
        $order->customer_name = $data['customer_name'];
        $order->customer_phone = $data['customer_phone'];
        $order->merchant_id = '';
        $order->sub_merchant_id = '';
        $order->fee = env('PLATFORM_FEE');
        $order->description = 'dual';
        $order->status = TransOrder::WAITING_PAYMENT;

        $ref = explode('-', $order->order_id);
        $payload = [
            'sessionID'=> $this->getSeesionId(),
            'merchantID'=>env('KIOSBANK_MERCHANT_ID'),
            'productID'=>$data['code'],
            'customerID'=>$data['phone'],
            'referenceID'=>$ref[2],
        ];
        $res_json =  $this->http('POST',self::INQUIRY,$payload);
        $res_json = $res_json->json();
        Log::info($payload, $res_json);

        if($res_json['rc'] == '00')
        {
            if ($res_json['productID'] == '520021' || $res_json['productID'] == '520011') {
                $order->sub_total = $res_json['data']['total'];
                $order->total = $order->sub_total + $order->fee;
                // $order->description = $res_json['data']['noHandphone'] ?? $res_json['data']['idPelanggan'].'-'.$res_json['productID'].'-'.$res_json['data']['nama'];
                // $res_json['data']['idPelanggan'] = $res_json['data']['noHandphone'] ?? $res_json['data']['idPelanggan'];
                // $res_json['data']['noReferensi'] = $res_json['referenceID'] ?? $res_json['data']['noReferensi'];
                // $res_json['data']['noReferensi'] = $res_json['data']['noReferensi'] ?? '-';

                // $res_json['data']['status'] = $res_json['data']['status'] ?? ($res_json['description'] ?? '-');
                // $res_json['data']['harga'] = $res_json['data']['harga'] ?? ($order->sub_total ?? '0');

                // $res_json['data']['nama'] = $res_json['data']['nama'] ?? ($res_json['data']['data']['nama'] ?? '-');
                // $res_json['data']['nominalProduk'] = $res_json['data']['nominalProduk'] ?? ($res_json['data']['data']['nominalProduk'] ?? ($order->sub_total ?? '0'));
                $res_json['description'] = 'INQUIRY';

                $order->save();
                $order->log_kiosbank()->updateOrCreate(['trans_order_id' => $order->id],[
                    'data' => $res_json
                ]);
                return $order;

            }
            else {
                $order->sub_total = $res_json['data']['harga'] ?? $res_json['data']['total'] ?? $res_json['data']['totalBayar'] ?? $res_json['data']['tagihan'];
                // $order->description = $res_json['data']['noHandphone'] ?? $res_json['data']['idPelanggan'].'-'.$res_json['productID'].'-'.$res_json['data']['nama'];
                $order->total = $order->sub_total + $order->fee;
                // $res_json['data']['idPelanggan'] = $res_json['data']['noHandphone'] ?? $res_json['data']['idPelanggan'];
                // $res_json['data']['noReferensi'] = $res_json['referenceID'] ?? $res_json['data']['noReferensi'];
                // $res_json['data']['noReferensi'] = $res_json['data']['noReferensi'] ?? '-';

                // $res_json['data']['status'] = $res_json['data']['status'] ?? ($res_json['description'] ?? '-');
                // $res_json['data']['harga'] = $res_json['data']['harga'] ?? ($order->sub_total ?? '0');

                // $res_json['data']['nama'] = $res_json['data']['nama'] ?? ($res_json['data']['data']['nama'] ?? '-');
                // $res_json['data']['nominalProduk'] = $res_json['data']['nominalProduk'] ?? ($res_json['data']['data']['nominalProduk'] ?? ($order->sub_total ?? '0'));

                $res_json['description'] = 'INQUIRY';
                $order->save();
                $order->log_kiosbank()->updateOrCreate(['trans_order_id' => $order->id],[
                    'data' => $res_json
                ]);
                return $order;
            }
        }

        return $res_json;
    }


    public function cek()
    {
        // $cek = $this->cekDeposit();
        // return $cek;

        return Http::withOptions(["verify"=>false])
            ->withDigestAuth($this->username,$this->password)
            ->post($this->baseUrl.self::CEK_DEPOSIT)->json();
    }
}
