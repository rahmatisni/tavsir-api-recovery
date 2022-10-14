<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStatusOrderReqeust;
use App\Http\Requests\PaymentOrderRequest;
use App\Http\Requests\TavsirProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tavsir\TrOrderRequest;
use App\Http\Requests\Tavsir\TrCategoryRequest;
use App\Http\Requests\TsOrderConfirmRequest;
use App\Http\Requests\VerifikasiOrderReqeust;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Tavsir\TrProductResource;
use App\Http\Resources\Tavsir\TrCartSavedResource;
use App\Http\Resources\Tavsir\TrOrderResource;
use App\Http\Resources\TravShop\TsOrderResource;
use App\Http\Resources\Tavsir\TrCategoryResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\PaymentMethod;
use App\Models\PgJmto;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransPayment;
use App\Models\TransSaldo;
use App\Models\Voucher;
use Carbon\Carbon;

class TavsirController extends Controller
{
    function productList(Request $request)
    {
        $data = Product::byTenant()->when($filter = $request->filter, function ($q) use ($filter) {
            return $q->where('name', 'like', "%$filter%")
                ->orwhere('sku', 'like', "%$filter%");
        })->when($category_id = $request->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->when($is_active = request()->is_active, function ($q) use ($is_active) {
            return $q->where('is_active', $is_active);
        })->orderBy('updated_at', 'desc')->get();
        return response()->json(TrProductResource::collection($data));
    }

    public function productStore(TavsirProductRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new Product();
            $data->tenant_id = auth()->user()->tenant_id;
            $data->fill($request->all());
            $data->save();
            $data->customize()->sync($request->customize);
            DB::commit();
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function productShow(Product $id)
    {
        return response()->json(new ProductResource($id));
    }
    function productById($id)
    {
        $data = Product::findOrfail($id);
        // return response()->json(new TsProducDetiltResource($data));
        return response()->json(new ProductResource($data));
    }
    public function productUpdate(TavsirProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();
            $product->fill($request->all());
            $product->tenant_id = auth()->user()->tenant_id;
            $product->save();
            $product->customize()->sync($request->customize);
            DB::commit();
            return response()->json($product);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function productDestroy(Product $product)
    {
        if (request()->ids) {
            $product->whereIn('id', request()->ids)->delete();
            return response()->json($product);
        } else {
            $product->delete();
            return response()->json($product);
        }
    }


    function categoryList()
    {
        $data = Category::byTenant()->orderBy('name')->get();

        return response()->json(TrCategoryResource::collection($data));
    }

    function categoryStore(TrCategoryRequest $request)
    {
        $data = new Category();
        $data->fill($request->all());
        $data->tenant_id = auth()->user()->tenant_id;
        $data->save();
        return response()->json($data);
    }

    public function categoryShow(Category $category)
    {
        return response()->json($category);
    }

    public function categoryUpdate(TrCategoryRequest $request, Category $category)
    {
        $category->update($request->all());
        $category->tenant_id = auth()->user()->tenant_id;
        return response()->json($category);
    }

    public function categoryDestroy(Category $category)
    {
        $category->delete();
        return response()->json($category);
    }

    function order(TrOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = TransOrder::find($request->id);
            if (!$data) {
                $data = new TransOrder();
                $data->order_type = TransOrder::ORDER_TAVSIR;
                $data->order_id = 'TAV-' . date('YmdHis');
                $data->status = TransOrder::CART;
            }
            $data->rest_area_id = auth()->user()->tenant->rest_area_id ?? null;
            $data->tenant_id = auth()->user()->tenant_id;
            $data->business_id = auth()->user()->business_id;
            $data->casheer_id = auth()->user()->id;
            $data->detil()->delete();
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

                $sub_total += $order_detil->total_price;

                $order_detil_many[] = $order_detil;
            }

            $data->sub_total = $sub_total;
            $data->total = $data->sub_total + $data->fee + $data->service_fee;

            $data->save();
            $data->detil()->saveMany($order_detil_many);

            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    function CountNewTNG()
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
            ->count();

        return response()->json(['count' => $data]);
    }

    function CountCarSaved()
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
            ->where('order_type', '=', TransOrder::ORDER_TAVSIR)
            ->where('status', '=', TransOrder::CART)
            ->count();

        return response()->json(['count' => $data]);
    }

    function CartDelete(Request $request)
    {
        $data = TransOrder::whereIn('id', $request->id)
            ->where('tenant_id', '=', auth()->user()->tenant_id)
            ->where('order_type', '=', TransOrder::ORDER_TAVSIR)
            ->where('status', '=', TransOrder::CART)
            ->get();

        $deleteDetail = TransOrderDetil::whereIn('trans_order_id', $request->id)->delete();
        $data->each->delete();

        return response()->json($data);
    }

    function orderConfirm(TsOrderConfirmRequest $request, $id)
    {
        $data = TransOrder::findOrfail($id);
        if ($data->status != TransOrder::WAITING_CONFIRMATION_TENANT && $data->status != TransOrder::WAITING_OPEN) {
            return response()->json(['error' => 'Order ' . $data->status], 500);
        }

        $data->detil->whereNotIn('id', $request->detil_id)->each(function ($item) {
            $item->delete();
        });

        $data->sub_total = $data->detil->whereIn('id', $request->detil_id)->sum('total_price');
        $data->total = $data->sub_total + $data->fee + $data->service_fee;
        $data->status = TransOrder::WAITING_CONFIRMATION_USER;
        $data->confirm_date = Carbon::now();
        $data->save();

        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function PaymentMethod()
    {
        $paymentMethods = PaymentMethod::all();
        foreach ($paymentMethods as $value) {
            if ($value->code_name == 'pg_va_bri') {
                $fee = PgJmto::feeBriVa();
                if ($fee) {
                    $value->fee = $fee;
                    $value->save();
                }
            }

            if ($value->code_name == 'pg_va_mandiri') {
                $fee = PgJmto::feeMandiriVa();
                if ($fee) {
                    $value->fee = $fee;
                    $value->save();
                }
            }

            if ($value->code_name == 'pg_va_bni') {
                $fee = PgJmto::feeBniVa();
                if ($fee) {
                    $value->fee = $fee;
                    $value->save();
                }
            }
        }
        return response()->json($paymentMethods);
    }

    function OrderList(Request $request)
    {
        DB::enableQueryLog();
        $json = array();
        $data = TransOrder::when($status = request()->status, function ($q) use ($status) {
            if (is_array($status)) {
                $q->whereIn('status', $status);
            } else {
                $q->where('status', $status);
            }
        })
            ->when($statusnot = request()->statusnot, function ($q) use ($statusnot) {
                if (is_array($statusnot)) {
                    $q->whereNotIn('status', $statusnot);
                } else {
                    $q->whereNotIn('status', $statusnot);
                }
            })
            ->when($filter = request()->filter, function ($q) use ($filter) {
                return $q->where('order_id', 'like', "%$filter%");
            })->when($tenant_id = request()->tenant_id, function ($q) use ($tenant_id) {
                $q->where('tenant_id', $tenant_id);
            })->when($order_type = request()->order_type, function ($q) use ($order_type) {
                $q->where('order_type', $order_type);
            })->when($sort = request()->sort, function ($q) use ($sort) {
                if (is_array($sort)) {
                    foreach ($sort as $val) {
                        $jsonx = explode("&", $val);
                        $q->orderBy($jsonx[0], $jsonx[1]);
                    }
                }
            })
            ->get();
        // return response()->json([DB::getQueryLog(), $request->order, $json]);
        return response()->json(TrOrderResource::collection($data));
    }

    function OrderById($id)
    {
        $data = TransOrder::findOrfail($id);
        return response()->json(new TrOrderResource($data));
    }

    function PaymentOrder(PaymentOrderRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = TransOrder::find($request->id);
            $payment_method = PaymentMethod::findOrFail($request->payment_method_id);
            switch ($payment_method->code_name) {
                case 'cash':
                    if ($data->total > $request->cash) {
                        return response()->json(['message' => "Not Enough Balance"]);
                    }
                    $payment = new TransPayment();
                    $payment->trans_order_id = $data->id;
                    $payment->data = [
                        'cash' => $request->cash,
                        'total' => $data->total,
                        'kembalian' => $request->cash - $data->total
                    ];
                    $data->payment()->save($payment);
                    $data->payment_method_id = $request->payment_method_id;
                    $data->payment_id = $payment->id;
                    $data->pay_amount =  $request->cash;
                    $data->total = $data->total;
                    $data->status = TransOrder::DONE;
                    $data->save();
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
                        'total' => $data->total,
                        'tenant' => $data->tenant->name ?? '',
                        'voucher' => request()->voucher
                    ];
                    $payment = new TransPayment();
                    $payment->trans_order_id = $data->id;
                    $payment->data = $payment_payload;
                    $data->payment()->save($payment);
                    $data->payment_method_id = $request->payment_method_id;
                    $data->payment_id = $payment->id;
                    $data->total = $data->total + $data->service_fee;
                    $data->status = TransOrder::DONE;

                    $trans_saldo = TransSaldo::with('trans_invoice')->ByTenant()->first();
                    if (!$trans_saldo) {
                        $trans_saldo = TransSaldo::create([
                            'rest_area_id' => $data->rest_area_id,
                            'tenant_id' => auth()->user()->tenant_id,
                            'saldo' => $data->total,
                            'created_at' => Carbon::now(),
                        ]);
                    } else {
                        $trans_saldo->saldo += $data->total;
                        $trans_saldo->save();
                    }

                    $data->save();
                    break;

                default:
                    return response()->json(['error' => $payment_method->name . ' Coming Soon'], 500);
                    break;
            }
            DB::commit();
            return response()->json(new TrOrderResource($data));
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    function changeStatusOrder($id, ChangeStatusOrderReqeust $request)
    {
        $data = TransOrder::findOrFail($id);

        if ($data->status == TransOrder::DONE || $data->status == TransOrder::CANCEL) {
            return response()->json(['error' => 'Order Sudah ' . $data->status], 400);
        }

        $data->status = $request->status;
        $data->code_verif = random_int(1000, 9999);
        if ($request->status == TransOrder::CANCEL) { {
                $data->canceled_by = TransOrder::CANCELED_BY_CASHEER;
                $data->canceled_name = auth()->user()->name;
                $data->reason_cancel = $request->reason_cancel;
            }
            $data->save();

            return response()->json($data);
        }

        function sendNotif()
        {
            $hsl = sendNotif(
                array('dNSReUXRRQmuwAjfzfRcAC:APA91bEn-1Y4TFgc33bWorTzlmzj-Tr7WA1oIikUEjQBEX9Pu1BC8szlQ-iUwrBap2O_QF6ifXgZ4SzgPcSoU2JCXBX-J-IyPI1hthuBBbxE8Tcy0Vml-m7ldVirvJC5cosD7Y5g95Zq', 'cjOx95mNQEaC4S1q-0wP9I:APA91bFqykwgULm2RvfmnhtuhshHSgRAw99lOsbKigdb1rUEFvPMdhFaXbZmxDoOJCw5dd_x6Qdgv0K8hLByTpI0WajOiDBo2R3ZByGxWmHrTheKGgvF9Afp2cer3Rhb9rttFNxtEA78'),
                'testing',
                'testing'
            );
            return response()->json(['result' => $hsl]);
        }
    }
}
