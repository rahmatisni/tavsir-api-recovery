<?php

namespace App\Http\Controllers\API;

use App\Models\LogKiosbank;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TsCreatePaymentRequest;
use App\Http\Requests\TsOrderConfirmRequest;
use App\Http\Requests\TsOrderRequest;
use App\Http\Requests\VerifikasiOrderReqeust;
use App\Http\Resources\SaldoResource;
use App\Http\Resources\TravShop\TsOrderResource;
use App\Http\Resources\TravShop\TsProducDetiltResource;
use App\Http\Resources\TravShop\TsProductResource;
use App\Http\Resources\TravShop\TsTenantResource;
use App\Http\Resources\TravShop\TsRestAreaResource;
use App\Http\Resources\TsPaymentresource;
use App\Models\Bind;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\PaymentMethod;
use App\Models\PgJmto;
use App\Models\Product;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\User;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\TransPayment;
use App\Models\Voucher;
use App\Services\External\KiosBankService;
use App\Services\StockServices;
use App\Services\TransSharingServices;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TravShopController extends Controller
{
    protected $stock_service;
    protected $trans_sharing_service;
    protected $kiosBankService;

    public function __construct(StockServices $stock_service, TransSharingServices $trans_sharing_service, KiosBankService $kiosBankService)
    {
        $this->stock_service = $stock_service;
        $this->trans_sharing_service = $trans_sharing_service;
        $this->kiosBankService = $kiosBankService;
    }

    public function restArea(Request $request)
    {
        $data = RestArea::when($name = $request->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        });
        $data = $data->get();

        if ($request->lat && $request->lon) {
            $data = $data->filter(function ($item) use ($request) {
                return $this->haversine($item->latitude, $item->longitude, $request->lat, $request->lon, $request->distance ?? 1);
            });
        }

        return response()->json(TsRestAreaResource::collection($data));
    }

    public function tenant(Request $request)
    {
        $data = Tenant::when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
            return $q->where('rest_area_id', $rest_area_id);
        })->when($category = $request->category, function ($q) use ($category) {
            return $q->where('category', $category);
        })->when($name = $request->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        })->when($product = $request->product, function ($q) use ($product) {
            return $q->whereHas('product', function ($q) use ($product) {
                return $q->where('name', 'like', "%$product%");
            });
        })->get();

        return response()->json(TsTenantResource::collection($data));
    }

    public function tenantById($id)
    {
        $data = Tenant::findOrFail($id);

        return response()->json(new TsTenantResource($data));
    }

    public function tenantByCategory()
    {
        $data = Tenant::get()->groupBy('category')->map(function ($item, $key) {
            return TsTenantResource::collection($item);
        });
        return response()->json($data);
    }

    public function product(Request $request)
    {
        $data = Product::when($name = $request->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        })->when($tenant_id = $request->tenant_id, function ($q) use ($tenant_id) {
            return $q->where('tenant_id', $tenant_id);
        })->when($category_id = $request->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->get();
        return response()->json(TsProductResource::collection($data));
    }

    public function productById($id)
    {
        $data = Product::findOrfail($id);
        return response()->json(new TsProducDetiltResource($data));
    }

    public function order(TsOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new TransOrder;
            $tenant = Tenant::find($request->tenant_id);
            $data->order_type = TransOrder::ORDER_TAKE_N_GO;
            $data->order_id = ($tenant->rest_area_id ?? '0').'-'.($tenant->id ?? '0').'-TNG-' . date('YmdHis');
            $data->rest_area_id = $tenant->rest_area_id;
            $data->tenant_id = $request->tenant_id;
            $data->business_id = $tenant->business_id;
            $data->customer_id = $request->customer_id;
            $data->customer_name = $request->customer_name;
            $data->customer_phone = $request->customer_phone;
            $data->merchant_id = $request->merchant_id;
            $data->sub_merchant_id = $request->sub_merchant_id;
            $order_detil_many = [];
            $data->save();

            $sub_total = 0;
            foreach ($request->product as $k => $v) {
              
                $product = Product::find($v['product_id']);
                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->base_price = $product->price;
                $order_detil->price = $product->price;
                $customize_x = array();
                foreach ($v['customize'] as $key => $value) {
                    $customize_y = collect($product->customize)->where('id', $value)->first();
                    if ($customize_y) {
                        $pilihan = collect($customize_y->pilihan);
                        $pilihan = $pilihan->where('id', $v['pilihan'][$key])->first();
                        if ($pilihan) {
                            $customize_z = [
                                'customize_id' => $customize_y->id,
                                'customize_name' => $customize_y->name,
                                'pilihan_id' => $pilihan->id,
                                'pilihan_name' => $pilihan->name,
                                'pilihan_price' => (int)$pilihan->price,
                            ];
                            $customize_x[] = $customize_z;
                            $order_detil->price += $pilihan->price;
                        }
                    }
                }
                $order_detil->customize = json_encode($customize_x);
                $order_detil->qty = $v['qty'];
                $order_detil->total_price = $order_detil->price * $v['qty'];
                $order_detil->note = $v['note'];

                $sub_total += $order_detil->total_price;

                $order_detil_many[] = $order_detil;
            }
            $data->fee = env('PLATFORM_FEE');
            $data->sub_total = $sub_total;
            $data->total = $data->sub_total + $data->fee + $data->service_fee;
            $data->status = TransOrder::WAITING_CONFIRMATION_TENANT;
            $data->save();
            $data->detil()->saveMany($order_detil_many);


            DB::commit();
            $data = TransOrder::findOrfail($data->id);

            $fcm_token = User::where([['tenant_id', $request->tenant_id]])->get();
            $ids = array();
            foreach ($fcm_token as $val) {
                if ($val['fcm_token'] != null && $val['fcm_token'] != '')
                    array_push($ids, $val['fcm_token']);
            }
            if ($ids != '') {
                $payload = array(
                    'id' => $data->id,
                    'type' => 'click',
                    'action' => 'tavsir_order'
                );
                $result = sendNotif($ids, 'Info', 'Pemberitahuan order baru TAKE N GO ' . $data->order_id, $payload);
            }

            return response()->json(new TsOrderResource($data));
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function orderCustomer($id, Request $request)
    {
        $tanggal_awal = $request->tanggal_awal;
        $tanggal_akhir = $request->tanggal_akhir;

        $tanggal_sub = null;
        $tanggal_end = null;

        if($request->hari){
            $now = CarbonImmutable::now();
            $tanggal_sub = $now->subDay($request->hari);
            $tanggal_end = $now->endOfDay();
        }

        $kategori = [];
        if($request->kategori){
            $kategori = ProductKiosBank::where('sub_kategori', $request->kategori)->pluck('kode')->toArray();
        }
        
        $data = TransOrder::with('detil')->where('customer_id', $id)
            ->when($status = request()->status, function ($q) use ($status) {
                return $q->where('status', $status);
            })->when($order_id = request()->order_id, function ($q) use ($order_id) {
                return $q->where('order_id', 'like', "%$order_id%");
            })->when($order_type = request()->order_type, function ($q) use ($order_type) {
                return $q->where('order_type', $order_type);
            })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
                return $q->where('tenant_id', $tenant_id);
            })->when(($tanggal_awal && $tanggal_akhir), function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_awal,
                        $tanggal_akhir . ' 23:59:59'
                    ]
                );
            })->when($request->hari, function ($q) use ($tanggal_sub, $tanggal_end) {
                return $q->whereBetween(
                    'created_at',
                    [
                        $tanggal_sub,
                        $tanggal_end,
                    ]
                );
            })
            ->orderByDesc('created_at')->get();

            if(count($kategori) > 0)
            {
                $data = $data->filter(function($item)use ($kategori){
                    if($item->order_type == TransOrder::ORDER_TRAVOY){
                        $kategori_id = explode('-',$item->order_id)[0];
                        if(in_array($kategori_id, $kategori)){
                            return $item;
                        }
                    }
                });
            }
        return response()->json(TsOrderResource::collection($data));
    }

    public function orderById($id)
    {
        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    public function orderConfirm($id)
    {
        $data = TransOrder::findOrfail($id);
        if ($data->status != TransOrder::WAITING_CONFIRMATION_USER) {
            return response()->json(['error' => 'Order ' . $data->status], 500);
        }

        $data->status = TransOrder::WAITING_PAYMENT;
        $data->save();

        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    public function orderCancel($id)
    {
        $data = TransOrder::findOrfail($id);
        $data->status = TransOrder::CANCEL;
        $data->canceled_by = TransOrder::CANCELED_BY_CUSTOMER;
        $data->canceled_name = request()->name;
        $data->save();

        return response()->json(new TsOrderResource($data));
    }

    public function paymentMethod(Request $request)
    {
        $paymentMethods = PaymentMethod::all();
        $removes = [];

        if($request->trans_order_id)
        {
            $trans_order = TransOrder::with('tenant')->findOrfail($request->trans_order_id);
            $tenant = $trans_order->tenant;
            $tenant_is_verified = $tenant?->is_verified;

            if($tenant_is_verified === false && $trans_order->order_type != TransOrder::ORDER_TRAVOY)
            {
                $merchant = PgJmto::listSubMerchant();
                if($merchant->successful())
                {
                    $merchant = $merchant->json();
                    if($merchant['status'] == 'success'){
                        $merchant = $merchant['responseData'];
                        foreach ($merchant as $key => $value) {
                            if($value['email'] == $tenant->email)
                            {
                                $trans_order->tenant()->update([
                                    'is_verified' => 1,
                                    'sub_merchant_id' => $value['merchant_id']
                                ]);
                            }
                        }
                    }
                }
            }

            foreach ($paymentMethods as $value) 
            {
                $value->platform_fee = env('PLATFORM_FEE');
                $value->fee = 0;

                if($value->sof_id)
                {
                    if($tenant_is_verified || $trans_order->order_type == TransOrder::ORDER_TRAVOY)
                    {
                        $data = PgJmto::tarifFee($value->sof_id,$value->payment_method_id, $value->sub_merchant_id,$trans_order->sub_total);
                        $value->fee = $data;
                    }else{
                        $removes[] = $value->id;
                    }
                }
            }
        }

        $paymentMethods = $paymentMethods->whereNotIn('id',$removes);
        return response()->json($paymentMethods);
    }

    public function createPayment(TsCreatePaymentRequest $request, $id)
    {
        $payment_payload = [];
        $data = TransOrder::findOrfail($id);

        try {
            DB::beginTransaction();
            if (request()->order_from_qr == true) { 
                if ($data->status == TransOrder::CART || $data->status == TransOrder::PENDING || $data->status == null) {
                    $data->status = TransOrder::WAITING_PAYMENT;
                    $data->customer_id = $request->customer_id;
                }
            }

            if ($data->status != TransOrder::WAITING_PAYMENT) {

                return response()->json(['info' => $data->status], 422);
            }
            //Cek deposit
            if($data->order_type == TransOrder::ORDER_TRAVOY)
            {
                $deposit = $this->kiosBankService->cekDeposit();
                if($deposit['rc'] == '00')
                {
                    if((int) $deposit['deposit'] < $data->sub_total){
                        return response()->json(['info' => 'Deposit '.$deposit['deposit'].' < '.$data->sub_total], 422);
                    }
                }else{
                    return response()->json(['info' => 'Deposit ', 'data' => $deposit], 422);
                }
            }

            $data->payment_method_id = $request->payment_method_id;

            $res = 'Invalid';
            $payment_method = PaymentMethod::find($request->payment_method_id);
            $payment_method_fee = PgJmto::tarifFee(
                $payment_method->sof_id,
                $payment_method->payment_method_id,
                $payment_method->sub_merchant_id,
                $data->sub_total
            );
            switch ($payment_method->code_name) {
                case 'pg_va_mandiri':
                    $payment_payload = [
                        "sof_code" =>  $payment_method->code,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'Take N Go',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        "exp_date" =>  Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        "va_type" =>  "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => $data->tenant?->sub_merchant_id ?? '',
                    ];
                    $res = PgJmto::vaCreate(
                        $payment_method->code,
                        $data->order_id,
                        'Take N Go',
                        $data->sub_total + $data->fee,
                        $data->tenant->name ?? 'Travoy',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name
                    );
                    if ($res['status'] == 'success') {
                        $pay = null;
                        if ($data->payment === null) {
                            $pay = new TransPayment();
                            $pay->data = $res['responseData'];
                            $data->payment()->save($pay);
                        } else {
                            $pay = $data->payment;
                            $pay->data = $res['responseData'];
                            $pay->save();
                        }
                        $data->service_fee = $pay->data['fee'];
                        $data->total = $data->total + $data->service_fee;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                    break;
                case 'pg_va_bri':
                    $payment_payload = [
                        "sof_code" =>  $payment_method->code,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'Take N Go',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        "exp_date" =>  Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        "va_type" =>  "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => $data->tenant?->sub_merchant_id ?? '',
                    ];
                    $res = PgJmto::vaCreate(
                        $payment_method->code,
                        $data->order_id,
                        'Take N Go',
                        $data->sub_total + $data->fee,
                        $data->tenant->name ?? 'Travoy',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name
                    );
                    if ($res['status'] == 'success') {
                        $pay = null;
                        if ($data->payment === null) {
                            $pay = new TransPayment();
                            $pay->data = $res['responseData'];
                            $data->payment()->save($pay);
                        } else {
                            $pay = $data->payment;
                            $pay->data = $res['responseData'];
                            $pay->save();
                        }
                        $data->service_fee = $pay->data['fee'];
                        $data->total = $data->total + $data->service_fee;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                    break;
                case 'pg_va_bni':
                    $payment_payload = [
                        "sof_code" =>  $payment_method->code,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'Take N Go',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        "exp_date" =>  Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        "va_type" =>  "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => $data->tenant?->sub_merchant_id ?? '',
                    ];
                    
                    $res = PgJmto::vaCreate(
                        $payment_method->code,
                        $data->order_id,
                        'Take N Go',
                        $data->sub_total + $data->fee,
                        $data->tenant->name ?? 'Travoy',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name
                    );
                    if ($res['status'] == 'success') {
                        $pay = null;
                        if ($data->payment === null) {
                            $pay = new TransPayment();
                            $pay->data = $res['responseData'];
                            $data->payment()->save($pay);
                        } else {
                            $pay = $data->payment;
                            $pay->data = $res['responseData'];
                            $pay->save();
                        }
                        $data->service_fee = $pay->data['fee'];
                        $data->total = $data->total + $data->service_fee;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                    break;
                case 'tav_qr':
                    $voucher = Voucher::where('hash', request()->voucher)
                        ->where('is_active', 1)
                        ->where('rest_area_id', $data->tenant->rest_area_id)
                        ->first();
                    if (!$voucher) {
                        return response()->json(['error' => 'Voucher tidak ditemukan'], 500);
                    }

                    if ($voucher->balance < $data->total) {
                        return response()->json(['error' => 'Ballance tidak cukup'], 500);
                    }

                    $balance_now = $voucher->balance;
                    $voucher->balance -= $data->total;
                    $ballaceHistory = [
                        "trx_id" => $data->id,
                        "trx_order_id" => $data->order_id,
                        "trx_type" => 'Belanja',
                        "trx_area" => $data->tenant ? ($data->tenant->rest_area ? $data->tenant->rest_area->name : '') : '',
                        "trx_name" => $data->tenant ? $data->tenant->name : '',
                        "trx_amount" => $data->total,
                        "current_balance" => $voucher->balance,
                        "last_balance" => $balance_now,
                        "datetime" => Carbon::now()->toDateTimeString(),
                    ];
                    $dataHistori = $voucher->balance_history;
                    $dataHistori['data'] = array_merge([$ballaceHistory], $voucher->balance_history['data']);
                    $dataHistori['current_balance'] = $voucher->balance;
                    $voucher->balance_history = $dataHistori;
                    $voucher->qr_code_use = $voucher->qr_code_use + 1;
                    $voucher->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                    $voucher->save();

                    $payment_payload = [
                        'order_id' => $data->order_id,
                        'order_name' => 'Take N Go',
                        'amount' => $data->total,
                        'desc' => $data->tenant->name ?? 'Travoy',
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        'voucher' => $voucher->id
                    ];
                    $payment = new TransPayment();
                    $payment->trans_order_id = $data->id;
                    $payment->data = $payment_payload;
                    $data->payment()->save($payment);
                    $data->total = $data->total + $data->service_fee;
                    $data->status = TransOrder::PAYMENT_SUCCESS;
                    $data->save();
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                    $this->trans_sharing_service->calculateSharing($data);
                    $res = $data;

                    break;

                case 'pg_dd_bri':
                    $bind = Bind::where('id', $request->card_id)->first();
                    $bind_before = TransPayment::where('trans_order_id', $data->id)->first();
                    // if ($bind_before->data->bind_id) {

                    // }

                    dd($bind_before->data['bind_id']);
                   
                    if (!$bind) {
                        return response()->json(['message' => 'Card Not Found'], 404);
                    }
                    if (!$bind->is_valid) {
                        return response()->json(['message' => 'Card Not Valid'], 404);
                    }

                    $payment_payload = [
                        "sof_code" => $bind->sof_code,
                        "bind_id" => (string) $bind->bind_id,
                        "refnum" => $bind->refnum,
                        "card_no" => $bind->card_no,
                        "amount" => (string) $data->total,
                        "trxid" => $data->order_id,
                        "remarks" => $data->tenant->name ?? 'Travoy',
                        "phone" => $bind->phone,
                        "email" => $bind->email,
                        "customer_name" => $bind->customer_name,
                        "bill" => (string)$data->sub_total,
                        "fee" => (string)$data->fee,
                    ];
                    $respon = PgJmto::inquiryDD($payment_payload);
                    log::info($respon);
                    if ($respon->successful()) {
                        $res = $respon->json();
                        if ($res['status'] == 'ERROR') {
                            return response()->json($res, 400);
                        }
                        $res['responseData']['bind_id'] = $bind->bind_id;
                        $respon = $res['responseData'];
                        if ($data->payment === null) {
                            $payment = new TransPayment();
                            $payment->data = $respon;
                            $payment->trans_order_id = $data->id;
                            $payment->save();
                        } else {
                            $tans_payment = TransPayment::where('trans_order_id', $data->id)->first();
                            $tans_payment->data = $respon;
                            $tans_payment->save();
                        }
                        $data->service_fee = $payment_method->fee;
                        $data->total = $data->sub_total + $data->service_fee;
                        $data->save();
                        DB::commit();
                        return response()->json($res);
                    }
                    return response()->json($respon->json(), 400);
                    break;

                default:
                    return response()->json(['error' => $payment_method->name . ' Coming Soon'], 500);

                    break;
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage(), $payment_payload], 500);
        }
    }

    public function paymentByOrderId($id)
    {
        $data = TransOrder::findOrfail($id);
        if (!$data->payment) {
            return response()->json(['error' => 'Payment Not Found'], 404);
        }
        return response()->json(new TsPaymentresource($data->payment));
    }

    public function statusPayment(Request $request, $id)
    {
        $data = TransOrder::with('payment_method')->findOrfail($id);
        try {
            DB::beginTransaction();
            if ($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::DONE || $data->status == TransOrder::READY) {
                $kios = [];
                if($data->order_type == TransOrder::ORDER_TRAVOY){
                    $datalog = $data->log_kiosbank()->where('trans_order_id', $id)->first();
                    $adminBank = $datalog['data']['data']['adminBank'] ?? '000000000000';

                    $kios = $this->kiosBankService->cekStatus($data->sub_total, $data->order_id, $adminBank, $data->harga_kios);
                    Log::info($kios);
                    $kios['data']['harga_kios'] = $data->harga_kios;
                    $kios['data']['harga'] = $data->sub_total ?? '0';
                    // $kios['data']['nominalProduk'] = $kios['data']['nominalProduk'] ?? $datalog['data']['data']['nominalProduk'] ?? $kios['data']['harga'] ?? '0';
                    $kios['description'] = $kios['description'] ?? $kios['data']['status'] ?? $kios['data']['description'] ?? '-';

                   
                    
                    if($kios['rc'] == '00'){

                        // $kios['description'] = 'BERHASIL';
                        if(str_contains($kios['description'] ?? $kios['data']['status'], 'BERHASIL'))
                        {
                            $data->status = TransOrder::DONE;
                        }
                        if(str_contains($kios['description'] ?? $kios['data']['status'], 'SUKSES'))
                        {
                            $data->status = TransOrder::DONE;
                        }
                        else 
                        {
                            // $data->status = TransOrder::READY;
                            $data->status = TransOrder::DONE;

                        }
                    }
                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                        'data' => $kios
                    ]);
                    // $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                    //     'data' => $kios
                    // ]);
                    // if($kios['rc'] == '71'){
                    //     // Log::info($kios);
                    //     if(str_contains($kios['description'] ?? $kios['data']['status'], 'TRANSAKSI SEDANG DIPROSES'))
                    //     {
                    //          $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                    //                 'data' => $kios
                    //             ]);
                    //         $data->status = TransOrder::READY;
                    //     }
                    //     else {
                    //         return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                    //     }
                    // }
                    $data->save();
                    DB::commit();
                }
                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
            }

            if ($data->status != TransOrder::WAITING_PAYMENT) {
                return response()->json(['status' => $data->status, 'responseData' => $data->payment ?? '']);
            }

            if (!$data->payment) {
                return response()->json(['status' => $data->status, 'responseData' => null]);
            }

            $data_payment = $data->payment->data;
            if ($data->payment_method->code_name == 'pg_dd_bri') {
                if (!$request->otp) {
                    return response()->json([
                        "message" => "The given data was invalid.",
                        "errors" => [
                            "otp" => [
                                "The otp field is required."
                            ]
                        ]
                    ], 422);
                }
                $payload = $data_payment;
                $payload['otp'] = $request->otp;
                $res = PgJmto::paymentDD($payload);
                log::info($res);
                if ($res->successful()) {
                    $res = $res->json();

                    if ($res['status'] == 'ERROR') {
                        return response()->json([
                            "message" => "ERROR!",
                            "errors" => [
                               $res
                            ]
                        ], 422);
                    }

                    $respon = $res['responseData'];
                    if ($data->payment === null) {
                        $payment = new TransPayment();
                        $payment->data = $respon;
                        $data->payment()->save($payment);
                    } else {
                        $pay = TransPayment::where('trans_order_id', $data->id)->first();
                        $pay->data = $respon;
                        $pay->save();
                    }

                    $data->status = TransOrder::PAYMENT_SUCCESS;
                    if ($data->order_type == TransOrder::ORDER_TAVSIR) {
                        $data->status = TransOrder::DONE;
                    }
                    $data->save();
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                    $this->trans_sharing_service->calculateSharing($data);
                    DB::commit();
                    return $data;
                }
                return response()->json($res->json(), 400);
            }

            $res = PgJmto::vaStatus(
                $data_payment['sof_code'],
                $data_payment['bill_id'],
                $data_payment['va_number'],
                $data_payment['refnum'],
                $data_payment['phone'],
                $data_payment['email'],
                $data_payment['customer_name']
            );
            if ($res['status'] == 'success') {
                $res_data = $res['responseData'];
                $res_data['fee'] = $data_payment['fee'];
                $res_data['bill'] = $data_payment['bill'];
                $kios = [];

                if ($res_data['pay_status'] == '1') {
                    $data->status = TransOrder::PAYMENT_SUCCESS;
                    if ($data->order_type == TransOrder::ORDER_TAVSIR) {
                        $data->status = TransOrder::DONE;
                    }
                    $data->save();
                    if($data->order_type == TransOrder::ORDER_TRAVOY && $data->status != TransOrder::DONE){
                        if ($data->description == 'single'){
                            $kios = $this->kiosBankService->singlePayment($data->sub_total, $data->order_id, $data->harga_kios);
                        }
                        if ($data->description == 'dual'){
                            $datalog = $data->log_kiosbank()->where('trans_order_id', $id)->first();
                            $tagihan = $datalog['data']['data']['tagihan'] ?? $datalog['data']['data']['harga_kios'];
                            $admin = $datalog['data']['data']['adminBank'] ?? '000000000000';
                            $total = $datalog['data']['data']['total'] ?? $datalog['data']['data']['harga_kios'] ?? $tagihan;
                            $kios = $this->kiosBankService->dualPayment($data->sub_total, $data->order_id, $tagihan, $admin, $total);
                        }
                        $datalog = $data->log_kiosbank()->where('trans_order_id', $id)->first();
                        $kios['data']['harga'] = $kios['data']['harga'] ?? ($data->sub_total ?? '0');
                        // $kios['data']['nama'] = $kios['data']['nama'] ?? $datalog['data']['data']['nama'] ?? '-';
                        // $kios['data']['nominalProduk'] = $kios['data']['nominalProduk'] ?? $datalog['data']['data']['nominalProduk'] ?? '0';
                        $kios['description'] = $kios['description'] ?? $kios['data']['status'] ?? $kios['data']['description'] ?? '';
                        $kios['data']['harga_kios'] = $data->harga_kios;
                        $kios['data']['harga'] = $data->sub_total ?? '0';

                        if($kios['rc'] == '00')
                        {
                            if(str_contains($kios['description'] ?? $kios['data']['status'], 'BERHASIL'))
                            {
                                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                                    'data' => $kios, 
                                    'payment' => $kios,

                                ]);
                                $data->status = TransOrder::DONE;
                                $data->save();
                                DB::commit();
                                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                            }
                            if(str_contains($kios['description'] ?? $kios['data']['status'], 'SUKSES'))
                            {
                                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                                    'data' => $kios,
                                    'payment' => $kios,

                                ]);
                                $data->status = TransOrder::DONE;
                                $data->save();
                                DB::commit();
                                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                            }
                            else 
                            {
                                $kios['description'] = $kios['description'] ?? $kios['data']['description'];
                                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                                    'data' => $kios,
                                    'payment' => $kios,

                                ]);
                                $data->status = TransOrder::READY;
                                $data->save();
                                DB::commit();
                                return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                            }
                            // $data->status = TransOrder::DONE;
                            // $data->save();
                            // DB::commit();
                            // return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                        }
                        else {
                            $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id],[
                                'data' => $kios, 
                                'payment' => $kios,

                            ]);
                            $data->status = TransOrder::PAYMENT_SUCCESS;
                            $data->save();
                            DB::commit();
                            return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                        }
                    }
                    foreach ($data->detil as $key => $value) {
                        $this->stock_service->updateStockProduct($value);
                    }
                } else {
                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'kiosbank' => $kios]);
                }
                $data->payment()->update(['data' => $res_data]);
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => (string) $th], 500);
        }
    }

    public function saldo()
    {
        $data = Voucher::when($rest_area_id = request()->rest_area_id, function ($q) use ($rest_area_id) {
            return $q->where('rest_area_id', $rest_area_id);
        })
            ->when($username = request()->username, function ($q) use ($username) {
                return $q->where('username', $username);
            })->when($customer_id = request()->customer_id, function ($q) use ($customer_id) {
                return $q->where('customer_id', $customer_id);
            })->when($phone = request()->phone, function ($q) use ($phone) {
                return $q->where('phone', $phone);
            })->get();
        return response()->json(SaldoResource::collection($data));
    }

    public function verifikasiOrder($id, VerifikasiOrderReqeust $request)
    {
        $data = TransOrder::findOrFail($id);
        if ($data->code_verif == $request->code) {
            $data->status = TransOrder::DONE;
            $data->pickup_date = Carbon::now();
        } else {
            return response()->json([
                "message" => "The given data was invalid.",
                "errors" => [
                    "code" => [
                        "The code is invalid."
                    ]
                ]
            ], 422);
        }
        $data->save();

        return response()->json($data);
    }
}
