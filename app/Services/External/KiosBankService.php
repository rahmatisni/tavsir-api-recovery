<?php

namespace App\Services\External;

use App\Models\KiosBank\ProductKiosBank;
use App\Models\Product;
use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class KiosBankService
{
    protected $accountKisonBank;

    protected $operatorPulsa;

    function __construct()
    {
        $this->accountKisonBank = [
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

    function post($url, $header, $params = false)
    {
        $curl = curl_init();

        if ($params === false)
            $query = '';
        else
            $query = json_encode($params);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 800,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => array(
                $header,
                'content-type:application/json'
            ),
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return $err;
        } else {
            return $response;
        }
    }

    function auth_response($params, $uri, $request_method)
    {
        /*
            SESUAIKAN INI
        */
        $username = 'dji';
        $password = 'abcde';
        $nc = '1'; //berurutan 1,2,3..dst sesuai request
        $cnonce = uniqid();

        $a1 = md5($username . ':' . $params['Digest realm'] . ':' . $password);
        $a2 = md5($request_method . ':' . $uri);
        $response = md5($a1 . ':' . $params['nonce'] . ':' . $nc . ':' . $cnonce . ':' . $params['qop'] . ':' . $a2);
        $query = array(
            'username' => $username,
            'password' => $password,
            'realm' => $params['Digest realm'],
            'nonce' => $params['nonce'],
            'uri' => $uri,
            'qop' => $params['qop'],
            'nc' => $nc,
            'cnonce' => $cnonce,
            'opaque' => $params['opaque'],
            'response' => $response
        );
        $query_str = 'username="' . $query['username'] . '",realm="' . $query['realm'] . '",nonce="' . $query['nonce'] . '",uri="' . $query['uri'] . '",qop="' . $query['qop'] . '",nc="' . $query['nc'] . '",cnonce="' . $query['cnonce'] . '",response="' . $query['response'] . '",opaque="' . $query['opaque'] . '"';
        return $query_str;
    }

    public function signOn() : string
    {
        $full_url = env('KIOSBANK_URL').'/auth/Sign-On';

        $sign_on_response = $this->post($full_url, '');
        $response_arr = explode('WWW-Authenticate: ', $sign_on_response);

        $response_arr_1 = explode('error', $response_arr[1]);
        $response = trim($response_arr_1[0]);
        $auth_arr = explode(',', $response);
        $auth_sorted = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $auth_sorted[$key] = substr($val, 1, strlen($val) - 2);
        }
        $auth_query = $this->auth_response($auth_sorted, '/auth/Sign-On', 'POST');

        /*
	    SESUAIKAN INI
        */
        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $this->accountKisonBank);
        $res_json = $post_response->json();

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
        $full_url = env('KIOSBANK_URL').'/Services/get-Active-Product';

        $sign_on_response = $this->post($full_url, '');
        $response_arr = explode('WWW-Authenticate: ', $sign_on_response);

        $response_arr_1 = explode('error', $response_arr[1]);
        $response = trim($response_arr_1[0]);
        $auth_arr = explode(',', $response);
        $auth_sorted = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $auth_sorted[$key] = substr($val, 1, strlen($val) - 2);
        }
        $auth_query = $this->auth_response($auth_sorted, '/Services/get-Active-Product', 'POST');

        /*
	    SESUAIKAN INI
        */
        $body_params=array(
            'sessionID'=> $this->getSeesionId(),
            ...$this->accountKisonBank
        );
        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $body_params);
        $res_json = $post_response->json();

        return $res_json;
    }

    public function getProduct()
    {
        $product = $this->cekStatusProduct();
        $status_respon = $product['rc'] ?? '';
        if($status_respon == '00')
        {
            $data = ProductKiosBank::get();

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

    public function getListOperatorPulsa(){
        return $this->operatorPulsa;
    }

    public function listProductOperatorPulsa($prefix_id){
        $full_url = env('KIOSBANK_URL').'/Services/getPulsa-Prabayar';

        $sign_on_response = $this->post($full_url, '');
        $response_arr = explode('WWW-Authenticate: ', $sign_on_response);

        $response_arr_1 = explode('error', $response_arr[1]);
        $response = trim($response_arr_1[0]);
        $auth_arr = explode(',', $response);
        $auth_sorted = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $auth_sorted[$key] = substr($val, 1, strlen($val) - 2);
        }
        $auth_query = $this->auth_response($auth_sorted, '/Services/getPulsa-Prabayar', 'POST');

        /*
	    SESUAIKAN INI
        */
        $body_params=array(
            'sessionID'=> $this->getSeesionId(),
            'prefixID'=> $prefix_id,
            'merchantID' => env('KIOSBANK_MERCHANT_ID')
        );

        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $body_params);
        $res_json = $post_response->json();
        return $res_json;
        // if($res_json['rc'] == '00')
        // {
        //     $record = $res_json['record'];
        //     $new_record = [];
        //     foreach ($record as $key => $value) {
        //         $fee = (int) env('PLATFORM_FEE') ?? 0;
        //         $total = $fee + $value['price'];

        //         $value['platform_fee'] = $fee;
        //         $value['sub_total'] = $total;

        //         array_push($new_record, $value);
        //     }
        //     return $new_record;
        // }else{
        //     return $res_json;
        // }
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
        $order->order_id = $data['code'].'-'.$data['phone'].'-'.Carbon::now()->timestamp;
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
        $order->save();

        return $data;
    }

    public function singlePayment($sub_total,$order_id)
    {
        $full_url = env('KIOSBANK_URL').'/Services/SinglePayment';

        $sign_on_response = $this->post($full_url, '');
        $response_arr = explode('WWW-Authenticate: ', $sign_on_response);

        $response_arr_1 = explode('error', $response_arr[1]);
        $response = trim($response_arr_1[0]);
        $auth_arr = explode(',', $response);
        $auth_sorted = array();
        foreach ($auth_arr as $auth) {
            list($key, $val) = explode('=', $auth);
            $auth_sorted[$key] = substr($val, 1, strlen($val) - 2);
        }
        $auth_query = $this->auth_response($auth_sorted, '/Services/SinglePayment', 'POST');

        /*
	    SESUAIKAN INI
        */

        $order = explode('-', $order_id);

        $body_params=array(
            'total'=>$sub_total,
            'admin'=>'000000000000',
            'tagihan'=>$sub_total,
            'sessionID'=> $this->getSeesionId(),
            'productID'=>$order[0],
            'referenceID'=>$order_id,
            'merchantID'=>env('KIOSBANK_MERCHANT_ID'),
            'customerID'=>$order[1]
        );

        $post_response = Http::withOptions(['verify' => false,])
                  ->withHeaders(['Authorization' => 'Digest '.$auth_query])
                  ->post($full_url, $body_params);
        $res_json = $post_response->json();
        return $res_json;
    }


    public function cek()
    {
        $cek =  $this->getProduct();
       
        return $cek;
    }
}
