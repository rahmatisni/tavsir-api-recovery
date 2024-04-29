<?php

namespace App\Services\Payment;

use App\Models\Bind;
use App\Models\Constanta\PaymentMethodCode;
use App\Models\LaJmto;
use App\Models\LogKiosbank;
use App\Models\PaymentMethod;
use App\Models\PgJmto;
use App\Models\PgJmtoSnap;
use App\Models\TenantLa;
use App\Models\TransOrder;
use App\Models\TransPayment;
use App\Models\Voucher;
use App\Services\External\JatelindoService;
use App\Services\External\KiosBankService;
use App\Services\External\TravoyService;
use App\Services\StockServices;
use App\Services\TransSharingServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected StockServices $stock_service,
        protected TransSharingServices $trans_sharing_service,
        protected KiosBankService $serviceKiosBank,
        protected TravoyService $travoyService,
    ){}

    //CREATE
    public function create(PaymentMethod $payment_method, TransOrder $data, $additonal_data = []) : object
    {
        switch (true) {
            case Str::contains($payment_method->code_name, PaymentMethodCode::SNAP_VA):
                $result = $this->createSnapVA($payment_method, $data, $additonal_data);
                break;

            case Str::contains($payment_method->code_name, PaymentMethodCode::PG_VA):
                $result = $this->createPgVA($payment_method, $data, $additonal_data);
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::PG_DD):
                $result = $this->createDirectDebit(($additonal_data['card_id'] ?? 0), $data);
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::LINK):
                $result = $this->createLinkAja($payment_method, $data);
                break;
                    
            case Str::contains($payment_method->code_name, PaymentMethodCode::QRJTL):
                $result = $this->createQRISVA($payment_method, $data, $additonal_data);
                break;
                        
            case Str::contains($payment_method->code_name, PaymentMethodCode::QR):
                $voucher = $additonal_data['voucher'] ?? null;
                $customer_name = $additonal_data['customer_name'] ?? null;
                $customer_email = $additonal_data['customer_email'] ?? null;
                $customer_phone = $additonal_data['customer_phone'] ?? null;
                $result = $this->payQR($data, $voucher, $customer_name, $customer_email, $customer_phone);
                break;

            default:
                $result = $this->responsePayment(false, ['message' => $payment_method->name . ' Belum tersedia']);
        }
        return $result;
    }

    public function cekStatus(TransOrder $data, $additonal_data = []) : object
    {
        $payment_method = $data->payment_method;
        switch (true) {
            case Str::contains($payment_method->code_name, PaymentMethodCode::SNAP_VA):
                $result = $this->statusSnapVA($data);
                break;

            case Str::contains($payment_method->code_name, PaymentMethodCode::PG_VA):
                $result = $this->statusVA($data);
                break;
            
            case $payment_method->code_name == PaymentMethodCode::PG_DD_BRI:
                $result = $this->statusDirectDebitBri($data);
                break;
            
            case $payment_method->code_name == PaymentMethodCode::PG_DD_MANDIRI:
                $result = $this->statusDirectDebitMandiri($data, $additonal_data);
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::LINK):
                $result = $this->statusLinkAja($data);
                break;

            case Str::contains($payment_method->code_name, PaymentMethodCode::QRJTL):
                $result = $this->statusQRISPG($data);
                break;

                
            default:
                $result = $this->responsePayment(false, ['message' => $payment_method->name . ' Belum tersedia']);
        }
        return $result;
    }

    public function statusOrder(TransOrder $data, $additonal_data = [])
    {
        $result = $this->cekStatus($data, $additonal_data);
        if($result->status != true){
            return $result;
        }

        $data->status = TransOrder::PAYMENT_SUCCESS;
        if ($data->order_type === TransOrder::ORDER_TRAVOY) {
            $data->save();
            Db::commit();
            return $this->payKios($data);
        }
        if ($data->order_type === TransOrder::ORDER_DEREK_ONLINE) {
            $data->save();

            $travoy = $this->travoyService->detailDerek($data->id, ($additonal_data['id_user'] ?? null), ($additonal_data['token'] ?? null));
            $result->data['travoy'] = $travoy ?? '';
            return $result;
        }
        if ($data->order_type == TransOrder::POS) {
            $data->status = TransOrder::DONE;
        }

        foreach ($data->detil as $value) {
            $this->stock_service->updateStockProduct($value);
        }
        $this->trans_sharing_service->calculateSharing($data);
        $data->save();

        return $result;
    }

    public function createSnapVA($payment_method, $trans_order, $additonal_data) : object
    {
        $status = false;
        $res = PgJmtoSnap::vaCreate(
            sof_code: $payment_method->code,
            bill_id: $trans_order->order_id,
            bill_name: 'GetPay',
            amount: $trans_order->total - $trans_order->service_fee,
            customer_name: $trans_order->customer_name ?? ($trans_order->tenant->name ?? 'Travoy'),
            phone: $additonal_data['customer_phone'] ?? $trans_order->customer_phone ?? ($trans_order->tenant->phone ?? '08123456789'),
            email: env('APP_ENV') == 'testing' ? 'rahmatisni@gmail.com' : ($additonal_data['customer_email'] ?? $trans_order->tenant->email ?? 'travoy@jmto.co.id'),
            desc: $trans_order->tenant->name ?? 'Travoy',
            sub_merchant_id: $trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id
        );
        $code = ($res['responseData']['responseSnap']['responseCode'] ?? false);
        $exp = $res['responseData']['responseSnap']['virtualAccountData']['expiredDate'];
        $kalkulasi = $trans_order->sub_total + $trans_order->addon_total + $trans_order->fee;
        $res['fee'] = $res['responseData']['responseSnap']['virtualAccountData']['totalAmount']['value'] - $kalkulasi;
        $res['responseData']['exp_date'] = $exp;
        if($code == 2002700){
            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res['responseData'],
                'inquiry' => $res['responseData'],
            ]);

            $status = true;
        }
        return $this->responsePayment(
            status: $status,
            data: $res
        );
    }

    public function createPgVA($payment_method, $trans_order, $additonal_data) : object
    {
        $status = false;
        $fee = 0;
        $res = PgJmto::vaCreate(
            sof_code: $payment_method->code,
            bill_id: $trans_order->order_id,
            bill_name: 'GetPay',
            amount: $trans_order->total,
            desc: $trans_order->tenant->name ?? 'Travoy',
            phone: $additonal_data['customer_phone'] ?? $trans_order->customer_phone ?? ($trans_order->tenant->phone ?? '08123456789'),
            email: env('APP_ENV') == 'testing' ? 'rahmatisni@gmail.com' : ($additonal_data['customer_email'] ?? $trans_order->tenant->email ?? 'travoy@jmto.co.id'),
            customer_name: $trans_order->customer_name,
            sub_merchant_id:$trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id
        );

        if (($res['status'] ?? null) == 'success') {
            $trans_order->payment()->updateOrCreate([
                    'trans_order_id' => $trans_order->id
                ],[
                    'data' => $res['responseData'],
                    'inquiry' => $res['responseData']
                ]);
            $status = true;
            $fee = $res['responseData']['fee'] ?? 0;
        }

        return $this->responsePayment(
            status: $status,
            data: $res,
            fee: $fee
        );
    }


    public function createQRISVA($payment_method, $trans_order, $additional_data) : object
    {
        $status = false;
        $fee = 0;
        $res = PgJmto::vaCreate(
            sof_code: $payment_method->code,
            bill_id: $trans_order->order_id,
            bill_name: 'GetPay',
            amount: $trans_order->total,
            desc: $trans_order->tenant->name ?? 'Travoy',
            phone: $additonal_data['customer_phone'] ?? $trans_order->customer_phone ?? ($trans_order->tenant->phone ?? '08123456789'),
            email: env('APP_ENV') == 'testing' ? 'rahmatisni@gmail.com' : ($additonal_data['customer_email'] ?? $trans_order->tenant->email ?? 'travoy@jmto.co.id'),
            customer_name: $trans_order->customer_name,
            sub_merchant_id:$trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id
        );

        if (($res['status'] ?? null) == 'success') {
            $trans_order->payment()->create([
                'data' => $res['responseData'],
                'inquiry' => $res['responseData'],
            ]);
            $status = true;
            $fee = $res['responseData']['fee'] ?? 0;
        }

        return $this->responsePayment(
            status: $status,
            data: $res,
            fee: $fee
        );
    }

    public function createLinkAja($payment_method, $trans_order) : object
    {
        $status = false;
        $fee = 0;
        $data_la = TenantLa::where('tenant_id', $trans_order->Tenant->id)->firstOrFail();
        $res = LaJmto::qrCreate(
            sof_code: $payment_method->code,
            bill_id: $trans_order->order_id,
            bill_name: 'GetPay',
            amount: $trans_order->total,
            desc: $trans_order->tenant->name ?? 'Travoy',
            phone: $trans_order->tenant->phone,
            email: $trans_order->tenant->email,
            customer_name: $trans_order->nomor_name,
            sub_merchant_id:$trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id,
            data_la: $data_la
        );

        if (($res['status'] ?? null) == 'success') {
            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res['responseData'],
                'inquiry' => $res['responseData']
            ]);
            $status = true;
            $fee = $res['responseData']['fee'] ?? 0;
        }

        return $this->responsePayment(
            status: $status,
            data: $res,
            fee: $fee
        );
    }

    public function createDirectDebit($card_id, $trans_order) : object
    {
        $status = false;
        $fee = 0;

        $bind = Bind::where('id', $card_id)->first();
        $bind_before = TransPayment::where('trans_order_id', $trans_order->id)->first();
        if (!$bind) {
            return $this->responsePayment(
                status: $status,
                data: [
                    'message' => 'Card Not Found'
                ]
            );
        }
        if (!$bind->is_valid) {
            return $this->responsePayment(
                status: $status,
                data: [
                    'message' => 'Card Not Valid'
                ]
            );
        }

        $payment_payload = [
            "sof_code" => $bind->sof_code ?? $bind_before->data['sof_code'],
            "bind_id" => $bind->bind_id ?? $bind_before->data['bind_id'],
            "card_no" => $bind->card_no ?? $bind_before->data['card_no'],
            "amount" => (string) $trans_order->sub_total,
            "trxid" => $trans_order->order_id,
            "remarks" => $trans_order->tenant->name ?? 'Travoy',
            "phone" => $bind->phone ?? $bind_before->data['phone'],
            "email" => $bind->email ?? $bind_before->data['email'],
            "fee" => (string) $trans_order->fee,
            "customer_name" => $bind->customer_name ?? $bind_before->data['customer_name'],
            "submerchant_id" => $trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id
        ];

        $respon = PgJmto::inquiryDD($payment_payload);

        if ($respon->successful()) {
            $res = $respon->json();
            if ($res['status'] == 'ERROR') {
                $status = false;
                return $this->responsePayment($status, $res, $fee);
            }
            $res['responseData']['bind_id'] = $bind->bind_id;
            $res['responseData']['card_id'] = $card_id;
            $respon = $res['responseData'];
            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res['responseData'],
                'inquiry' => $res['responseData']
            ]);

            $status = true;
            $fee = $respon['fee'];

            return $this->responsePayment($status, $res, $fee);
        }
    }

    public function payQR($data, $voucher, $customer_name, $customer_email, $customer_phone) : object
    {
        $status = false;
        $voucher = Voucher::where('hash', $voucher)
                ->where('is_active', 1)
                ->where('rest_area_id', $data->tenant->rest_area_id)
                ->first();

        if (!$voucher) {
            return $this->responsePayment(
                status: $status,
                data: [
                    'message' => 'Voucher tidak ditemukan'
                ]
            );
        }

        if ($voucher->balance < $data->total) {
            return $this->responsePayment(
                status: $status,
                data: [
                    'message' => 'Ballance tidak cukup'
                ]
            );
        }

        $balance_now = $voucher->balance;
        $voucher->balance -= $data->total;
        $ballaceHistory = [
            "trx_id" => $data->id,
            "trx_order_id" => $data->order_id,
            "trx_type" => 'Belanja',
            "trx_area" => $data->tenant->rest_area->name ?? '',
            "trx_name" => $data->tenant ?? '',
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
        $status = true;

        $payment_payload = [
            'order_id' => $data->order_id,
            'order_name' => 'GetPay',
            'amount' => $data->total,
            'desc' => $data->tenant->name ?? 'Travoy',
            'phone' => $customer_phone,
            'email' => $customer_email,
            'customer_name' => $customer_name,
            'voucher' => $voucher->id
        ];
        $data->payment()->updateOrCreate([
            'data$data_id' => $data->id
        ],[
            'data' => $payment_payload,
            'inquiry' => $payment_payload
        ]);

        return $this->responsePayment(
            status: $status,
            data: $payment_payload
        );
    }
    //END CREATE

    public function statusDirectDebitBri($trans_order)
    {
        $status = false;

        $data_payment = $trans_order->payment->data;
        $payload = $data_payment;
        $payload['submerchant_id'] = $trans_order->sub_merchant_id;
        $payload['payrefnum'] = $data_payment['refnum'];
        $res = PgJmto::statusDD($payload);

        if (!$res->successful()) {
            return $this->responsePayment($status, $res->json());
        }

        $res = $res->json();
        if ($res['status'] == 'ERROR') {
            return $this->responsePayment(
                status: $status,
                data: $res
            );
        }

        $is_dd_pg_success = $res['responseData']['pay_refnum'] ?? null;
        if ($is_dd_pg_success == null) {
            return $this->responsePayment(
                status: $status,
                data: $res
            );
        }

        $respon = $res['responseData'];
        $trans_order->payment()->updateOrCreate([
            'trans_order_id' => $trans_order->id
        ],[
            'data' => $respon,
        ]);

        $status = true;

        return $this->responsePayment(
            status: $status,
            data: $res
        );
    }

    public function statusDirectDebitMandiri($trans_order,$additonal_data)
    {
        $status = false;

        $otp = $additonal_data['otp'] ?? null;
        $card_id = $additonal_data['card_id'] ?? null;
        if($otp == null){
            return $this->responsePayment($status, [
                "message" => "The given data was invalid.",
                "errors" => [
                    "otp" => [
                        "The otp field is required."
                    ]
                ]
            ]);
        }
        
        $data_payment = $trans_order->payment->data;
        $data_payment['submerchant_id'] = $trans_order->sub_merchant_id;
        $data_payment['otp'] = $otp;
        $data_payment['card_id'] = $card_id;

        $res = PgJmto::paymentDD($data_payment);
        if (!$res->successful()) {
            return $this->responsePayment($status, $res->json());
        }

        $res = $res->json();
        if ($res['status'] == 'ERROR') {
            return $this->responsePayment(
                status: $status,
                data: [
                    "message" => "ERROR!",
                    "errors" => [
                        $res
                    ]
                ]
            );
        }

        $res['responseData']['card_id'] = $additonal_data['card_id'] ?? '';
        $respon = $res['responseData'];
        $trans_order->payment()->updateOrCreate([
            'trans_order_id' => $trans_order->id
        ],[
            'data' => $respon,
            'payment' => $respon,
        ]);

        $status = true;
        unset($res['requestData']);

        return $this->responsePayment($status, $res);
    }

    public function statusSnapVA($trans_order)
    {
        $status = false;
        $payment_data = $trans_order->payment->data['responseSnap']['virtualAccountData'] ?? [];
        $res = PgJmtoSnap::vaStatus($payment_data);

        if(($res['responseCode'] ?? null) == '2002700'){
            $status = ($res['virtualAccountData']['paymentFlagStatus'] ?? 0) == 1 ? true : false;
            $res = $trans_order->payment->data;
            if($status == true){
                // $res['responseData']['pay_status'] = 1;
                $res['pay_status'] = 1;

                // $status_order = TransOrder::PAYMENT_SUCCESS;
                $status_order = TransOrder::PAYMENT_SUCCESS;
            }else{
                // $res['responseData']['pay_status'] = 0;
                $res['pay_status'] = 0;
                $status_order = TransOrder::WAITING_PAYMENT;   
            }
            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res,
            ]);
        }

        $log = $trans_order->log_kiosbank()->where('trans_order_id', $trans_order->id)->first();
        $res = [
            'status' => $status_order,
            'responseData'=>$res,
            'kiosbank' => $log?->data ?? []
        ];
        return $this->responsePayment($status, $res);
    }

    public function statusVA($trans_order)
    {
        $status = false;
        $payment_data = $trans_order->payment->data;
        $res = PgJmto::vaStatus(
            $payment_data['sof_code'],
            $payment_data['bill_id'],
            $payment_data['va_number'],
            $payment_data['refnum'],
            $payment_data['phone'],
            $payment_data['email'],
            $payment_data['customer_name'],
            $trans_order->sub_merchant_id
        );

        if(($res['status'] ?? null) == 'success'){
            $status = ($res['responseData']['pay_status'] ?? 0) == 1 ? true : false;
            if($status == true){
                $res['responseData']['pay_status'] = 1;
            }else{
                $res['responseData']['pay_status'] = 0;
            }

            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res['responseData'],
            ]);
        }

        return $this->responsePayment($status, $res);
    }

    public function statusLinkAja($trans_order)
    {
        $data_payment = $trans_order->payment->data;
        $data_la = TenantLa::where('tenant_id', $trans_order->tenant_id)->firstOrFail();
        $res = LAJmto::qrStatus(
            $data_payment['bill_id'],
            $data_la
        );

        if(($res['status'] ?? null) == 'success'){
            $status = ($res['responseData']['pay_status'] ?? 0) == 1 ? true : false;
            unset($res['la_response']);

            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res,
            ]);
        }

        return $this->responsePayment($status, $res);
    }

    public function statusQRISPG($trans_order)
    {
        $data_payment = $trans_order->payment->data;
        $data_la = TenantLa::where('tenant_id', $trans_order->tenant_id)->firstOrFail();
        $res = PgJmto::QRStatus(
           
            $data_payment, $data_la
        );

        if(($res['status'] ?? null) == 'success'){
            $status = ($res['responseData']['pay_status'] ?? 0) == 1 ? true : false;
            unset($res['la_response']);

            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res,
            ]);
        }

        return $this->responsePayment($status, $res);
    }

    private function responsePayment($status = false, $data = null, $fee = 0) : object
    {
        return (object) [
            'status' => $status,
            'data' => $data,
            'fee' => $fee
        ];
    }
    
    public function payKios($data)
    {
        $status = false;
        $kios = [];

        if ($data->description == 'single') {
            $kios = $this->serviceKiosBank->singlePayment($data->sub_total, $data->order_id, $data->harga_kios);
            Log::info(['bayar depan => ', $kios]);
        }
        $datalog = $data->log_kiosbank;

        if ($data->description == 'dual') {
            if ($data->productKiosbank()->integrator == 'JATELINDO') {
                $data_log_kios = $data->log_kiosbank->data ?? [];
                $is_purchase = $data_log_kios['is_purchase'] ?? false;
                $is_advice = $data_log_kios['is_advice'] ?? false;
                $result_jatelindo = [];
                $is_time_out = false;
                $rc = null;
                if($is_purchase != true){
                    //1. Purchase
                    try {
                        $res_jatelindo = JatelindoService::purchase($data_log_kios);
                        $result_jatelindo = $res_jatelindo->json();
                        $data_log_kios = $result_jatelindo;
                        $rc = $result_jatelindo['bit39'] ?? '';
                        Log::info('Purchase rc = '.$rc);
                        $data_log_kios['is_purchase'] = true;
                        $data->log_kiosbank()->update(['data' => $data_log_kios]);
                        DB::commit();
                        $is_purchase = true;
                        if($rc == '18'){
                            //2. Advice
                            Log::info('Advice begin');
                            $is_advice = true;
                            $data_log_kios['is_advice'] = true;
                            $data->log_kiosbank()->update(['data' => $data_log_kios]);
                            DB::commit();
                            try {
                                $res_jatelindo = JatelindoService::advice($data_log_kios);
                                $result_jatelindo = $res_jatelindo->json();
                                $rc = $result_jatelindo['bit39'] ?? '';
                                Log::info('Advice rc = '.$rc);
                                if($rc == '18'){
                                    $is_time_out = true;
                                }else{
                                    $is_purchase = false;
                                }
                            } catch (\Throwable $e) {
                                Log::info('Advice timeout : '. $e->getMessage());
                                $is_time_out = true;
                            }
                            $data_log_kios = $result_jatelindo;
                            $data_log_kios['is_advice'] = true;
                            $data->log_kiosbank()->update(['data' => $data_log_kios]);
                            DB::commit();
                        }
                    } catch (\Throwable $e) {
                        Log::info('Purchase timeout & advice ='.$is_advice.'. '.$e->getMessage().' ');
                        if(!$is_advice){
                            try {
                                $data_log_kios['is_advice'] = true;
                                $data->log_kiosbank()->update(['data' => $data_log_kios]);
                                DB::commit();

                                $res_jatelindo = JatelindoService::advice($data_log_kios);
                                $result_jatelindo = $res_jatelindo->json();
                                $rc = $result_jatelindo['bit39'] ?? '';
                                Log::info('Advice rc = '.$rc);
                                $data->log_kiosbank()->update(['data' => $data_log_kios]);
                                DB::commit();
                                $is_advice = true;
                            } catch (\Throwable $th) {
                                Log::info('Advice timeout : '. $th->getMessage());
                                $is_time_out = true;
                            }
                        }
                    }
                }

                if($is_purchase || $is_time_out){
                    $try = 1;
                    do {
                        $res_jatelindo = JatelindoService::repeat($data->log_kiosbank->data ?? []);
                        $result_jatelindo = $res_jatelindo->json();
                        $rc = $result_jatelindo['bit39'] ?? '';
                        $try++;
                        Log::info('Repeate ' . $try . ' rc = ' . $rc);
                    } while ($try <= 3 && $rc == '18');
                }

                if ($rc == '00') {
                    //return token listrik
                    $data->status = TransOrder::DONE;
                    array_push($result_jatelindo, ['is_sucess' => true]);
                    $log_kios = $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                        'data' => $result_jatelindo
                    ]);
                    $data->save();
                    DB::commit();
                    $info = JatelindoService::infoPelanggan($log_kios, $data->status);
                    $map = [
                        'status' =>  $data->status,
                        'kiosbank' => [
                            'data' => $info
                        ]
                    ];
                    return $this->responsePayment(true, $map);
                }else{
                    $data->status = TransOrder::READY;
                    $data->save();
                    DB::commit();
                }
                return $this->responsePayment(false, ['status' => 200, 'data' => JatelindoService::responseTranslation($result_jatelindo)]);
            }
            $tagihan = $datalog['data']['data']['tagihan'] ?? $datalog['data']['data']['harga_kios'];
            $admin = $datalog['data']['data']['adminBank'] ?? $datalog['data']['data']['AB'] ?? '000000000000';
            $total = $datalog['data']['data']['total'] ?? $datalog['data']['data']['harga_kios'] ?? $tagihan;
            $kios = $this->serviceKiosBank->dualPayment($data->sub_total, $data->order_id, $tagihan, $admin, $total);
            Log::info(['bayar depan => ', $kios]);
        }

        $kios['data']['harga'] = $kios['data']['harga'] ?? ($data->sub_total ?? '0');
        $kios['description'] = $kios['description'] ?? $kios['data']['status'] ?? $kios['data']['description'] ?? '';
        $kios['data']['harga_kios'] = $data->harga_kios;
        $kios['data']['harga'] = $data->sub_total ?? '0';

        $data->status = TransOrder::PAYMENT_SUCCESS;
        $status = true;

        $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
            'data' => $kios,
            'payment' => $kios,
        ]);
        if ($kios['rc'] == '00') {
            $data->status = TransOrder::PAYMENT_SUCCESS;
            $status_description = $kios['description'] ?? $kios['data']['status'];
           
            if(str_contains($status_description, 'BERHASIL') || str_contains($status_description, 'SUKSES')){
                $status = true;
                $data->status = TransOrder::DONE;
            }
        }
        $data->save();

        return $this->responsePayment(
            status: $status, 
            data: [
                'status' => $data->status, 
                'responseData' => $data->payment->data ?? '', 
                'kiosbank' => $kios
            ]
        );
    }
}