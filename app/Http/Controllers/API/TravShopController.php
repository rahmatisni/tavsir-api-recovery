<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TsCreatePaymentRequest;
use App\Http\Requests\TsOrderConfirmRequest;
use App\Http\Requests\TsOrderRequest;
use App\Http\Resources\SaldoResource;
use App\Http\Resources\TravShop\TsOrderResource;
use App\Http\Resources\TravShop\TsProducDetiltResource;
use App\Http\Resources\TravShop\TsProductResource;
use App\Http\Resources\TravShop\TsTenantResource;
use App\Http\Resources\TravShop\TsRestAreaResource;
use App\Http\Resources\TsPaymentresource;
use App\Models\PaymentMethod;
use App\Models\PgJmto;
use App\Models\Product;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\TransPayment;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TravShopController extends Controller
{
    function restArea(Request $request)
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

    function tenant(Request $request)
    {
        $data = Tenant::when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
            return $q->where('rest_area_id', $rest_area_id);
        })->when($name = $request->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        })->when($product = $request->product, function ($q) use ($product) {
            return $q->whereHas('product', function ($q) use ($product) {
                return $q->where('name', 'like', "%$product%");
            });
        })->get();

        return response()->json(TsTenantResource::collection($data));
    }

    function product(Request $request)
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

    function productById($id)
    {
        $data = Product::findOrfail($id);
        return response()->json(new TsProducDetiltResource($data));
    }

    function order(TsOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new TransOrder();
            $data->order_type = TransOrder::ORDER_TAKE_N_GO;
            $data->order_id = 'TNG-' . date('YmdHis');
            $data->tenant_id = $request->tenant_id;
            $data->business_id = $request->business_id;
            $data->customer_id = $request->customer_id;
            $data->merchant_id = $request->merchant_id;
            $data->sub_merchant_id = $request->sub_merchant_id;
            $order_detil_many = [];
            $data->save();

            // dd($request->all());

            foreach ($request->product as $k => $v) {
                $product = Product::find($v['product_id']);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
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
                                'pilihan_price' => $pilihan->price,
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

                $data->sub_total += $order_detil->total_price;

                $order_detil_many[] = $order_detil;
            }
            $data->fee = 2000;
            $data->total = $data->sub_total + $data->fee + $data->service_fee;
            $data->status = TransOrder::WAITING_CONFIRMATION;
            $data->save();
            $data->detil()->saveMany($order_detil_many);

            // Send Email
            // \Mail::to('test@email.com')->send(new \App\Mail\SendMail('Struk', 'struk'));

            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    function orderCustomer($id)
    {
        $data = TransOrder::fromTakengo()->with('detil')->where('customer_id', $id)
            ->when($status = request()->status, function ($q) use ($status) {
                return $q->where('status', $status);
            })->when($order_id = request()->order_id, function ($q) use ($order_id) {
                return $q->where('order_id', 'like', "%$order_id%");
            })->when($order_type = request()->order_type, function ($q) use ($order_type) {
                return $q->where('order_type', $order_type);
            })->get();
        return response()->json(TsOrderResource::collection($data));
    }

    function orderById($id)
    {
        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function orderConfirm(TsOrderConfirmRequest $request, $id)
    {
        $data = TransOrder::findOrfail($id);
        if($data->status != TransOrder::WAITING_CONFIRMATION)
        {
            return response()->json(['error' => 'Order '.$data->status], 500);
        }
        $dataConfirm = $data->detil->whereNotIn('id', $request->detil_id)->each(function ($item) {
            $item->delete();
        });

        $data->sub_total = $dataConfirm->('total_price');
        // $data->sub_total = $dataConfirm->options()->sum('total_price');
        $data->total = $data->sub_total + $data->fee + $data->service_fee;
        $data->status = TransOrder::WAITING_PAYMENT;
        $data->save();

        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function orderCancel($id)
    {
        $data = TransOrder::findOrfail($id);
        $data->status = TransOrder::CANCEL;
        $data->save();

        return response()->json(new TsOrderResource($data));
    }

    function paymentMethod()
    {
        $data = PaymentMethod::get();
        return response()->json($data);
    }

    function createPayment(TsCreatePaymentRequest $request, $id)
    {
        $payment_payload = [];
        try {
            DB::beginTransaction();

            $data = TransOrder::findOrfail($id);

            if(request()->order_from_qr == true)
            {
                if($data->status == TransOrder::CART || $data->status == TransOrder::PENDING || $data->status == null)
                {
                    $data->status = TransOrder::WAITING_PAYMENT;
                }
            }

            if($data->status != TransOrder::WAITING_PAYMENT){

                return response()->json(['info' => $data->status], 422);
            }
            $data->payment_method_id = $request->payment_method_id;

            $res = 'Invalid';
            $payment_method = PaymentMethod::findOrFail($request->payment_method_id);
            switch ($payment_method->code_name) {
                case 'pg_va_mandiri':
                    $payment_payload = [
                        "sof_code" =>  $payment_method->code_sof,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'Take N Go',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? '',
                        "exp_date" =>  Carbon::now()->addDay(1)->format('Y-m-d H:i:s'),
                        "va_type" =>  "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => '98'
                    ];
                    $res = PgJmto::vaCreate(
                        $payment_method->code_sof,
                        $data->order_id,
                        'Take N Go',
                        $data->total,
                        $data->tenant->name ?? '',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name
                    );
                    if ($res['status'] == 'success') {
                        $payment = new TransPayment();
                        $payment->trans_order_id = $data->id;
                        $payment->data = $res['responseData'];
                        $data->payment()->save($payment);
                        $data->service_fee = $payment->data->fee;
                        $data->total = $data->total + $data->service_fee;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                break;
                case 'pg_va_bri':
                    $payment_payload = [
                        "sof_code" =>  $payment_method->code_sof,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'Take N Go',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? '',
                        "exp_date" =>  Carbon::now()->addDay(1)->format('Y-m-d H:i:s'),
                        "va_type" =>  "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => '98'
                    ];
                    $res = PgJmto::vaCreate(
                        $payment_method->code_sof,
                        $data->order_id,
                        'Take N Go',
                        $data->total,
                        $data->tenant->name ?? '',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name
                    );
                    if ($res['status'] == 'success') {
                        $payment = new TransPayment();
                        $payment->trans_order_id = $data->id;
                        $payment->data = $res['responseData'];
                        $data->payment()->save($payment);
                        $data->service_fee = $payment->data->fee;
                        $data->total = $data->total + $data->service_fee;
                        $data->save();
                    } else {
                        return response()->json([$res], 500);
                    }
                break;
                case 'pg_va_bni':
                    $payment_payload = [
                        "sof_code" =>  $payment_method->code_sof,
                        'bill_id' => $data->order_id,
                        'bill_name' => 'Take N Go',
                        'amount' => (string) $data->total,
                        'desc' => $data->tenant->name ?? '',
                        "exp_date" =>  Carbon::now()->addDay(1)->format('Y-m-d H:i:s'),
                        "va_type" =>  "close",
                        'phone' => $request->customer_phone,
                        'email' => $request->customer_email,
                        'customer_name' => $request->customer_name,
                        "submerchant_id" => '98'
                    ];
                    $res = PgJmto::vaCreate(
                        $payment_method->code_sof,
                        $data->order_id,
                        'Take N Go',
                        $data->total,
                        $data->tenant->name ?? '',
                        $request->customer_phone,
                        $request->customer_email,
                        $request->customer_name
                    );
                    if ($res['status'] == 'success') {
                        $payment = new TransPayment();
                        $payment->trans_order_id = $data->id;
                        $payment->data = $res['responseData'];
                        $data->payment()->save($payment);
                        $data->service_fee = $payment->data->fee;
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
                    if(!$voucher){
                        return response()->json(['error' => 'Voucher tidak ditemukan'], 500);
                    }

                    if($voucher->balance < $data->total){
                        return response()->json(['error' => 'Ballance tidak cukup'], 500);
                    }

                    $balance_now = $voucher->balance;
                    $voucher->balance -= $data->total;
                    $ballaceHistory = [
                                "trx_id" => $data->id,
                                "trx_order_id" => $data->order_id,
                                "trx_type" => 'Belanja',
                                "trx_area" => $data->tenant ? ($data->tenant->rest_area ? $data->tenant->rest_area->name : ''): '',
                                "trx_name" => $data->tenant ? $data->tenant->name : '',
                                "trx_amount" => $data->total,
                                "current_balance" => $voucher->balance,
                                "last_balance" => $balance_now,
                                "datetime" => Carbon::now()->toDateTimeString(),
                    ];
                    $dataHistori = $voucher->balance_history;
                    $dataHistori['data'] = array_merge([$ballaceHistory],$voucher->balance_history['data']);
                    $dataHistori['current_balance'] = $voucher->balance;
                    $voucher->balance_history = $dataHistori;
                    $voucher->qr_code_use = $voucher->qr_code_use + 1;
                    $voucher->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                    $voucher->save();

                    $payment_payload = [
                        'order_id' => $data->order_id,
                        'order_name' => 'Take N Go',
                        'amount' => $data->total,
                        'desc' => $data->tenant->name ?? '',
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
                    $res = $data;

                break;

                default:
                    return response()->json(['error' => $payment_method->name . ' Coming Soon'], 500);

                break;
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage(),$payment_payload], 500);
        }
    }

    function paymentByOrderId($id)
    {
        $data = TransOrder::findOrfail($id);
        if (!$data->payment) {
            return response()->json(['error' => 'Payment Not Found'], 404);
        }
        return response()->json(new TsPaymentresource($data->payment));
    }

    function statusPayment($id)
    {
        try {
            DB::beginTransaction();

            $data = TransOrder::findOrfail($id);

            if($data->status == TransOrder::PAYMENT_SUCCESS){

                return response()->json(['status' => $data->status ,'responseData' => $data->payment->data ?? '']);
            }

            if($data->status != TransOrder::WAITING_PAYMENT){
                return response()->json(['status' => $data->status, 'responseData' => $data->payment ?? '']);
            }

            if(!$data->payment){
                return response()->json(['status' => $data->status, 'responseData' => null]);
            }

            $data_payment = $data->payment->data;
            $res = PgJmto::vaStatus(
                $data_payment->sof_code,
                $data_payment->bill,
                $data_payment->va_number,
                $data_payment->refnum,
                $data_payment->phone,
                $data_payment->email,
                $data_payment->customer_name
            );
            if($res['status'] == 'success'){
                $res_data = $res['responseData'];
                $res_data['fee'] = $data_payment->fee;
                $res_data['bill'] = $data_payment->bill;
                if($res_data['pay_status'] == '1'){
                    $data->status = TransOrder::PAYMENT_SUCCESS;
                    $data->save();
                }else{
                    return response()->json(['status' => $data->status, 'responseData' => $data->payment->data ?? '']);
                }
                $data->payment()->update([ 'data' => $res_data]);
            }
            DB::commit();
            return response()->json($res);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function saldo()
    {
        $data = Voucher::when($rest_area_id = request()->rest_area_id, function($q) use ($rest_area_id) {
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
}
