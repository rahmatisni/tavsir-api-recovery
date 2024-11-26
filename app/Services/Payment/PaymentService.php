<?php

namespace App\Services\Payment;

use App\Jobs\AutoAdviceJob;
use App\Models\Bind;
use App\Models\Constanta\PaymentMethodCode;
use App\Models\LaJmto;
use App\Models\LogJatelindo;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

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
            case Str::contains($payment_method->is_snap, 1) && $payment_method->payment_method_code == 'VA':
                $result = $this->createSnapVA($payment_method, $data, $additonal_data);
                break;
            
            case Str::contains($payment_method->is_snap, 1) && $payment_method->payment_method_code == 'DD':
                $result = $this->createSnapDirectDebit($additonal_data['card_id'], $data);
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::SNAP_DD):
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
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::MIDTRANS_VA_BCA):
                $result = $this->midtransVaBca($data);
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::MIDTRANS_CARD):
                $result = $this->midtransCard($data);
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::MIDTRANS_GOPAY):
                $result = $this->midtransGopay($data);
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
            case Str::contains($payment_method->is_snap, 1) && $payment_method->payment_method_code == 'VA':
                $result = $this->statusSnapVA($data);
                break;
            
            case Str::contains($payment_method->is_snap, 1) && $payment_method->payment_method_code == 'DD':
                $result = $this->statusSnapDirectDebit($data, $additonal_data['otp']);
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
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::MIDTRANS_VA_BCA):
                $result = $this->midtransCekStatus($data);   
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::MIDTRANS_CARD):
                $result = $this->midtransCekStatus($data);   
                break;
            
            case Str::contains($payment_method->code_name, PaymentMethodCode::MIDTRANS_GOPAY):
                $result = $this->midtransCekStatus($data);   
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
            $data->status = TransOrder::WAITING_PAYMENT;
            return $result;
        }
        $data->status = TransOrder::PAYMENT_SUCCESS;

        $data->payment()->updateOrCreate([
            'trans_order_id' => $data->id
        ],[
            'data' => $result->data['responseData'] ?? $result->data,
            'payment' => $result->data['responseData'] ?? $result->data
        ]);
        
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
        if ($data->order_type == TransOrder::ORDER_HU) {
                $data->status = TransOrder::DONE;
                $data->save();
                DB::commit();
                $travoy = $this->travoyService->detailHU($data->id);
                $result->data['travoy'] = $travoy ?? '';
                return $result;
    
                // return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '', 'travoy' => $travoy ?? '']);
        }
        if ($data->order_type == TransOrder::POS) {
            $data->status = TransOrder::DONE;
        }
        if ($data->order_type == TransOrder::ORDER_SELF_ORDER && $data->tenant->in_selforder === 4){
            $this->trans_sharing_service->calculateSharing($data);
            $data->status = TransOrder::DONE;
            $data->save();
            return $result;
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
            amount: $trans_order->total,
            customer_name: $trans_order->customer_name ?? ($trans_order->tenant->name ?? 'Travoy'),
            phone: $additonal_data['customer_phone'] ?? $trans_order->customer_phone ?? ($trans_order->tenant->phone ?? '08123456789'),
            email: env('APP_ENV') == 'testing' ? 'rahmatisni@gmail.com' : ($additonal_data['customer_email'] ?? $trans_order->tenant->email ?? 'travoy@jmto.co.id'),
            desc: $trans_order->tenant->name ?? 'Travoy',
            sub_merchant_id: $trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id,
            prefix : env('PREFIX_PG'),
            data : $trans_order
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
            sub_merchant_id:$trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id,
            order_type: $trans_order->order_type
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
        $res = PgJmto::qrJTLCreate(
            sof_code: $payment_method->code,
            bill_id: $trans_order->order_id,
            bill_name: 'GetPay',
            amount: $trans_order->total,
            desc: $trans_order->tenant->name ?? 'Travoy',
            phone: $additonal_data['customer_phone'] ?? $trans_order->customer_phone ?? ($trans_order->tenant->phone ?? '08123456789'),
            email: env('APP_ENV') == 'testing' ? 'rahmatisni@gmail.com' : ($additonal_data['customer_email'] ?? $trans_order->tenant->email ?? 'travoy@jmto.co.id'),
            customer_name: $trans_order->customer_name,
            sub_merchant_id:$trans_order->tenant?->sub_merchant_id ?? $trans_order->sub_merchant_id,
            order_type: $trans_order->order_type
        );

        log::info(['Nih Yo', $res]);
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
           $res['responseData']['qr_string'] = $res['responseData']['qrString'] ;

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

        $data_payment = $trans_order->payment->inquiry;
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
            'payment' => $respon,
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
        
        $data_payment = $trans_order->payment->inquiry;
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
        $status_order = null;

        $payment_data = $trans_order->payment->inquiry['responseSnap']['virtualAccountData'] ?? [];
        $res = PgJmtoSnap::vaStatus($payment_data);
        if(($res['responseCode'] ?? null) == '2002600'){
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
                'payment' => $res,
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
        $payment_data = $trans_order->payment->inquiry ?? $trans_order->payment->data;
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
            if ($status == true) {
                $res['responseData']['pay_status'] = 1;
            } else {
                $res['responseData']['pay_status'] = 0;
            }
            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ], [
                'data' => $res['responseData'],
                'payment' => $res['responseData']
            ]);
        }

        return $this->responsePayment($status, $res);
    }

    public function statusLinkAja($trans_order)
    {
        $data_payment = $trans_order->payment->inquiry;
        $data_la = TenantLa::where('tenant_id', $trans_order->tenant_id)->firstOrFail();
        $res = LAJmto::qrStatus(
            $data_payment['bill_id'],
            $data_la
        );
        $status = false;
        if(($res['status'] ?? null) == 'success'){
            $status = ($res['responseData']['pay_status'] ?? 0) == 1 ? true : false;
            unset($res['la_response']);

            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res,
                'payment' => $res,
            ]);
        }

        return $this->responsePayment($status, $res);
    }

    public function statusQRISPG($trans_order)
    {
        $data_payment = $trans_order->payment->inquiry;
        // $data_la = TenantLa::where('tenant_id', $trans_order->tenant_id)->firstOrFail();

        // $res = PgJmto::QRStatus(
        //     $data_payment
        // );

        // if($trans_order->sub_total == 100000) {
        //     $res = [

        //         "status" =>  "success",
        //         "rc" =>  "0000",
        //         "rcm" =>  "success",
        //         "responseData" =>  [
        //             "sof_code" =>  "FELLO",
        //             "bill_id" =>  "155337",
        //             "reff_number" =>  "QR20240516152712000000",
        //             "status" =>  true,
        //             "pay_status" => 1
        //             ]
    
        //     ];   
        //     $status = $res['responseData']['status'];
        //     $trans_order->payment()->updateOrCreate([
        //         'trans_order_id' => $trans_order->id
        //     ], [
        //         'data' => $res,
        //         'payment' => $res,
        //     ]);
        // }
        // else if($trans_order->sub_total == 50000) {
        //     $res = [

        //         "status" =>  "success",
        //         "rc" =>  "0000",
        //         "rcm" =>  "success",
        //         "responseData" =>  [
        //             "sof_code" =>  "FELLO",
        //             "bill_id" =>  "155337",
        //             "reff_number" =>  "QR20240516152712000000",
        //             "status" =>  false,
        //             "pay_status" => 0
        //             ]
        //     ];
        //     $trans_order->payment()->updateOrCreate([
        //         'trans_order_id' => $trans_order->id
        //     ], [
        //         'data' => $res,
        //         'payment' => $res,
        //     ]);
        //     $status = $res['responseData']['status'];

        // } else {
            $res = PgJmto::QRStatus(
                $data_payment
            );
            if (($res['status'] ?? null) == 'success') {
                $status = ($res['responseData']['status'] ?? 0) == true ? true : false;
                unset($res['la_response']);
                $res['pay_status'] = ($status === true ? 1:0);
                $trans_order->payment()->updateOrCreate([
                    'trans_order_id' => $trans_order->id
                ], [
                    'data' => $res,
                    'payment' => $res,
                ]);
            }
        // }

        // true sight
        // if(($res['status'] ?? null) == 'success'){
        //     $status = ($res['responseData']['status'] ?? 0) == true ? true : false;
        //     unset($res['la_response']);

        //     $trans_order->payment()->updateOrCreate([
        //         'trans_order_id' => $trans_order->id
        //     ],[
        //         'data' => $res,
        //         'payment' => $res,
        //     ]);
        // }
        // dump($status, $res);
        // dd('x');
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
        $data_log_kios = $data->log_kiosbank->inquiry ??  $data->log_kiosbank->data ??[];
        $datalog = $data_log_kios;
        if ($data->description == 'dual') {
            if ($data->productKiosbank()->integrator == 'JATELINDO') {
                $is_purchase = $data_log_kios['is_purchase'] ?? false;

                $repeate_date = $data_log_kios['repeate_date'] ?? Carbon::now()->toDateTimeString();
                $repeate_count = $data_log_kios['repeate_count'] ?? 0;

                $data_log_kios['repeate_date'] = $repeate_date;
                $data_log_kios['repeate_count'] = $repeate_count;
                $rc = null;
                $result_jatelindo = null;

                if($is_purchase != true){
                    //1. Purchase
                    try {
                        $data_log_kios['is_purchase'] = true;
                        $data->log_kiosbank()->update(['data' => $data_log_kios, 'payment' => $data_log_kios]);
                        $res_jatelindo = JatelindoService::purchase($data_log_kios, $data);
                        $result_jatelindo = $res_jatelindo->json();
                        $data_log_kios = $result_jatelindo;
                        $data->log_kiosbank()->update(['data' => $data_log_kios, 'payment' => $data_log_kios]);
                        $rc = $result_jatelindo['bit39'] ?? '';
                        Log::info('Purchase rc = '.$rc);
                        DB::commit();
                        if($rc == '18' || $rc == '13' || $rc == '96'){
                            //Auto AdviceJob
                            Log::info('Dispatch Auto AdviceJob reason rc '.$rc);
                            AutoAdviceJob::dispatch([ 'id' => $data->id])->delay(now()->addSecond(35));
                        }
                    } catch (\Throwable $e) {
                        Log::info('Dispatch Auto AdviceJob reason timeout '.$e->getMessage());
                        $data->log_jatelindo()->updateOrCreate([
                            'trans_order_id' => $data->trans_order_id,
                            // 'type' => LogJatelindo::repeat,
                            'type' => LogJatelindo::purchase,
                            'request' => $data_log_kios,
                            'response' => [$e->getMessage()],
                        ]);
                        AutoAdviceJob::dispatch([ 'id' => $data->id])->delay(now()->addSecond(35));
                    }
                }

                if ($rc == '00') {
                    //return token listrik
                    $data->status = TransOrder::DONE;
                    array_push($result_jatelindo, ['is_sucess' => true]);
                    $log_kios = $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                        'data' => $result_jatelindo,
                        'payment' => $result_jatelindo
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
                return $this->responsePayment(true, [
                    'status' => $data->status, 
                    'data' => JatelindoService::responseTranslation($result_jatelindo), 
                    'repeate_date' => $datalog->data['repeate_date'] ?? Carbon::now()->toDateString(),
                    'repate_count' => $datalog->data['repeate_count'] ?? $repeate_count,
                ]);
            }
            $tagihan = $datalog['data']['tagihan'] ?? $datalog['data']['harga_kios'];
            $admin = $datalog['data']['adminBank'] ?? $datalog['data']['AB'] ?? '000000000000';
            $total = $datalog['data']['total'] ?? $datalog['data']['harga_kios'] ?? $tagihan;
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

    public function repeatManual($id)
    {
        $data = TransOrder::with('log_kiosbank')->findOrFail($id);
        $data_log_kios = $data->log_kiosbank->payment ?? ($data->log_kiosbank->inquiry ?? []);

        $temp_repeate_date = $data_log_kios['repeate_date'] ?? Carbon::now()->addMinute(5)->toDateTimeString();
        $temp_repeate_count = $data_log_kios['repeate_count'] ?? 0;

        if(Carbon::parse($temp_repeate_date)->diffInMinutes(Carbon::now()) >= 35){
            $temp_repeate_date = Carbon::now()->addMinute(5)->toDateTimeString();
            $temp_repeate_count = 0;
        }

        if($temp_repeate_count >=3){
            $data_error =  [
                'kode' => 00, 
                'keterangan' => 'TRANSAKI SUSPECT,MOHON HUBUNGI CUSTOMER SERVICE', 
                'message' => 'TRANSAKI SUSPECT,MOHON HUBUNGI CUSTOMER SERVICE'
            ];

            return $this->responsePayment(false, [
                'status' => $data->status, 
                'data' => $data_error,
                'repeate_date' => $temp_repeate_date,
                'repate_count' => $temp_repeate_count,
                'id' => $id
            ]);
        }

        if($temp_repeate_count >=3){
            $data_error =  [
                'kode' => 00, 
                'keterangan' => 'TRANSAKI SUSPECT,MOHON HUBUNGI CUSTOMER SERVICE', 
                'message' => 'TRANSAKI SUSPECT,MOHON HUBUNGI CUSTOMER SERVICE'
            ];

            return $this->responsePayment(false, [
                'status' => $data->status, 
                'data' => $data_error,
                'repeate_date' => $temp_repeate_date,
                'repate_count' => $temp_repeate_count,
                'id' => $id
            ]);
        }
        
        try {
            $is_success = $data_log_kios['is_success'] ?? false;
            if(($data->status == TransOrder::PAYMENT_SUCCESS || $data->status == TransOrder::READY)  && !$is_success && $data->order_type == TransOrder::ORDER_TRAVOY && $data->description == 'dual'){
                $data_log_kios = $data->log_kiosbank->inquiry ?? ($data->log_kiosbank->data ?? []);

                $data_log_kios['repeate_date'] = Carbon::now()->toDateTimeString();
                $data_log_kios['repeate_count'] = $temp_repeate_count + 1;

                $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                    'data' => $data_log_kios,
                    'payment' => $data_log_kios
                ]);

                $res_jatelindo = JatelindoService::repeat($data_log_kios, $data);
                $result_jatelindo = $res_jatelindo->json();
                $rc = $result_jatelindo['bit39'] ?? '';
                $result_jatelindo['repeate_date'] = $data_log_kios['repeate_date'];
                $result_jatelindo['repeate_count'] = $data_log_kios['repeate_count'];
                $temp = $result_jatelindo;

                if ($rc == '00') {
                    $data->status = TransOrder::DONE;
                    $result_jatelindo['is_success'] = true;

                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                        'data' => $result_jatelindo,
                        'payment' => $result_jatelindo
                    ]);
                    $data->save();
                    $info = JatelindoService::infoPelanggan($data_log_kios, $data->status);
                    $map = [
                        'status' =>  $data->status,
                        'kiosbank' => [
                            'data' => $info
                        ]
                    ];
                    return $this->responsePayment(true, $map);
                }else{
                    $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                        'data' => $result_jatelindo,
                        'payment' => $result_jatelindo
                    ]);
                   
                }

                return $this->responsePayment(true, [
                    'status' => $data->status, 
                    'data' => JatelindoService::responseTranslation($data_log_kios),
                    'repeate_date' => $temp['repeate_date'] ?? null,
                    'repate_count' => $temp['repeate_count'] ?? 0,
                    'id' => $id
                ]);
            }
            abort(404);
        } catch (\Throwable $th) {
            Log::info('Advice timeout : '. $th->getMessage());
            $data->log_kiosbank()->updateOrCreate(['trans_order_id' => $data->id], [
                'data' => $data_log_kios,
                'payment' => $data_log_kios
            ]);
            return $this->responsePayment(false, [
                'status' => $data->status, 
                'data' => JatelindoService::responseTranslation($data_log_kios),
                'message' => $th->getMessage(),
                'repeate_date' => $data_log_kios['repeate_date'] ?? Carbon::now()->toDateTimeString(),
                'repate_count' => $data_log_kios['repeate_count'] ?? 0,
                'id' => $id
            ]);
        }
    }

    public function createSnapDirectDebit($card_id, $trans_order) : object
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
            'amount' => (string) $trans_order->sub_total .'.00',
            'bindId' => $bind->bind_id ?? $bind_before->data['bind_id'],
            'accountName' => $bind->customer_name ?? $bind_before->data['customer_name'],
            'phoneNo' => $bind->phone ?? $bind_before->data['phone'],
            'email' => $bind->email ?? $bind_before->data['email'],
            'sofCode' => $bind->sof_code ?? $bind_before->data['sof_code'],
            'remarks' => $trans_order->tenant->name ?? 'Travoy',
        ];

        $respon = PgJmtoSnap::paymentDD($payment_payload);

        if ($respon->successful()) {
            $res = $respon->json();
            if (substr($res['responseCode'], 0, 3) != '200') {
                $status = false;
                return $this->responsePayment($status, $res, $fee);
            }
            $fee = $respon->json('additionalInfo.feeAmount.value');
            $res['bind_id'] = $bind->bind_id;
            $res['sof_code'] = $bind->sof_code;
            $res['card_id'] = $card_id;
            $res['responseData']['fee'] = $fee;
            $res['responseData']['exp_date'] = Carbon::now()->addHour();

            $trans_order->payment()->updateOrCreate([
                'trans_order_id' => $trans_order->id
            ],[
                'data' => $res,
                'inquiry' => $res
            ]);

            $status = true;
            return $this->responsePayment($status, $res, $fee);
        }
    }

    public function statusSnapDirectDebit($trans_order, $otp)
    {
        $trans_payment = $trans_order->payment->inquiry;
        $param = [
            'originalReferenceNo' => $trans_payment['referenceNo'],
            'otp' => $otp,
            'sofCode' => $trans_payment['sof_code'],
            'bindId' => $trans_payment['bind_id']
        ];
        return $this->verifyPaymentSnapDD($param);
    }

    public function verifyPaymentSnapDD($param)
    {
        $status = false;
        $payload = [
            'originalReferenceNo'=> $param['originalReferenceNo'],
            'type'=> 'payment',
            'otp'=> $param['otp'],
            'deviceId'=> '12345679237',
            'channel'=> 'mobilephone',
            'sofCode'=> $param['sofCode'],
            'bindId'=> $param['bindId'],
        ];

        $res = PgJmtoSnap::bindValidateDD($payload);
        if ($res->successful()) {
            $response = $res->json();
            if (substr($response['responseCode'], 0, 3) == '200') {
                $status = true;
            }
        }

        return $this->responsePayment($status, $res->json());
    }

    public function statusSnapDD($trans_order)
    {
        $status = false;

        $data_payment = $trans_order->payment->inquiry;
        $payload = [
            'originalReferenceNo' => $data_payment['referenceNo'],
            'bindId' => $data_payment['bind_id'],
            'sofCode' => $data_payment['sof_code'],
        ];
        $res = PgJmtoSnap::statusDD($payload);

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
            'payment' => $respon,
        ]);

        $status = true;

        return $this->responsePayment(
            status: $status,
            data: $res
        );
    }

    public function midtransCreate(TransOrder $data, $method)
    {
        if($data?->payment?->inquiry) {
            return $this->responsePayment(
                status: true,
                data: [
                    'responseData' => [
                        'fee' => 0,
                        'exp_date' => Carbon::now()->addDay()->format('c'),
                        ...$data->payment->inquiry
                    ]
                ]
            );
        }
        // Set your Merchant Server Key
        Config::$serverKey = config('midtrans.server_key');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        Config::$isProduction = config('midtrans.is_production');
        // Set sanitization on (default)
        Config::$isSanitized = config('midtrans.is_sanitized');
        // Set 3DS transaction for credit card to true
        Config::$is3ds = config('midtrans.is_3ds');

        $payload = [
            "transaction_details"=> [
                "order_id"=> $data->id,
                "gross_amount"=> $data->total
            ],
            "enabled_payments"=> [$method],
        ];

        try {
            $result = Snap::createTransaction($payload);
            $payment = [
                'token' => $result->token,
                'redirect_url' => $result->redirect_url,
            ];

            $data->payment()->updateOrCreate([
                'trans_order_id' => $data->id
            ],[
                'data' => $payment,
                'inquiry' => $payment
            ]);
            
            return $this->responsePayment(
                status: true,
                data: [
                    'responseData' => [
                        'fee' => 0,
                        'exp_date' => Carbon::now()->addDay()->format('c'),
                        ...$payment
                    ]
                ]
            );
        }
        catch (\Exception $e) {
            return $this->responsePayment(
                status: false,
                data: $e->getMessage()
            );
        }
    }

    public function midtransNotificationCallback($data)
    {
        try {
            $status = false;
            $order = TransOrder::find($data['order_id'] ?? 0);
            $status = $data['status_code'] ?? null;
            if ($order && $status == '200') {
                Db::transaction(function() use ($order, $data, &$status) {
                    $order->status = TransOrder::PAYMENT_SUCCESS;
                    $order->save();

                    $order->payment()->updateOrCreate([
                        'trans_order_id' => $order->id
                    ],[
                        'data' => $data,
                        'payment' => $data
                    ]);
                    $status = true;
                });
            }
            return $this->responsePayment(
                status: $status,
                data: [
                    'order_id' => $order?->order_id,
                    'status' => $order?->status
                ]
            );
        }
        catch (\Exception $e) {
            return $this->responsePayment(
                status: false,
                data: $e->getMessage()
            );
        }
    }

    public function midtransCekStatus(TransOrder $data) 
    {
        Config::$serverKey = config('midtrans.server_key');
        $result = Http::withHeaders(['Authorization' => 'Basic ' . base64_encode(Config::$serverKey . ':')])->get(Config::getBaseUrl()."/v2/{$data?->id}/status");
        $status = false;
        if($result->json('status_code') == 200) {
           $status = true;
        }

        return $this->responsePayment(
            status: $status,
            data: $result->json()
        );
    }

    public function midtransVaBca(TransOrder $data)
    {
        return $this->midtransCreate($data, 'bca_va');
    }

    public function midtransCard(TransOrder $data)
    {
        return $this->midtransCreate($data, 'credit_card');
    }

    public function midtransGopay(TransOrder $data)
    {
        return $this->midtransCreate($data, 'gopay');
    }
}