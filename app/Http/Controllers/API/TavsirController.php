<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentOrderRequest;
use App\Http\Requests\TavsirProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tavsir\TrOrderRequest;
use App\Http\Requests\Tavsir\TrCategoryRequest;
use App\Http\Requests\TsOrderConfirmRequest;
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
use App\Models\TransPayment;
use App\Models\Voucher;
use Carbon\Carbon;

class TavsirController extends Controller
{
    function productList(Request $request)
    {
        $data = Product::byTenant()->when($filter = $request->filter, function($q)use ($filter){
            return $q->where('name', 'like', "%$filter%")
                    ->orwhere('sku', 'like', "%$filter%");
        })->when($category_id = $request->category_id, function($q)use ($category_id){
            return $q->where('category_id', $category_id);
        })->when($is_active = request()->is_active, function($q)use ($is_active){
            return $q->where('is_active',$is_active);
        })->orderBy('updated_at','desc')->get();
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

    public function productShow(Product $product)
    {
        return response()->json(new ProductResource($product));
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

    public function porductDestroy(Product $product)
    {
        $product->delete();
        return response()->json($product);
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
            $data = new TransOrder();
            $data->order_type = TransOrder::ORDER_TAVSIR;
            $data->order_id = 'TAV-' . date('YmdHis');
            $data->tenant_id = auth()->user()->tenant_id;
            $data->business_id = auth()->user()->tenant->business_id;
            $order_detil_many = [];
            $data->save();

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
            $data->fee = 0;
            $data->total = $data->sub_total + $data->fee + $data->service_fee;
            $data->service_fee = 0;
            $data->total = $data->total + $data->service_fee;
            $data->save();

            switch ($request->action) {
                case TransOrder::ACTION_SAVE:
                    $data->status = TransOrder::CART ;
                    break;
                
                case TransOrder::ACTION_PAY:
                    
                    $payment = new TransPayment();
                    $data_pay = [
                        'total' => $data->total,
                        'pembayaran' => $request->pembayaran,
                        'kembalian' => $request->pembayaran - $data->total,
                    ];

                    $payment->data = $data_pay;
                    $data->payment()->save($payment);
                    break;
                default:
                    # code...
                    break;
            }
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
    

    function CartById($id)
    {
        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function CountCarSaved()
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
                    ->where('order_type', '=', TransOrder::ORDER_TAVSIR)       
                    ->where('status', '=', TransOrder::CART) 
                    ->where('is_save', '=', 1)
                    ->count();

        return response()->json(['count' => $data]);
    }

    function cartSaved()
    {
        $data =DB::table('trans_order AS O')
        ->where('tenant_id', '=', auth()->user()->tenant_id)
        ->where('order_type', '=', TransOrder::ORDER_TAVSIR) 
        ->where('status', '=', TransOrder::CART) 
        ->where('is_save', '=', 1)
        ->selectRaw('O.id, O.order_id, O.sub_total, O.fee, O.service_fee, O.total, O.business_id, O.tenant_id,
            O.merchant_id, O.sub_merchant_id')
        ->get();

        for ($i = 0; $i < count($data); $i++) {

            $detail = DB::table('trans_order_detil')->where('trans_order_id', $data[$i]->id)->get();
            $order_detil_many = [];

            foreach ($detail as $k => $v) 
            {
                //return response()->json($v);
                $product = Product::find($v->product_id);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data[$i]->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->price = $product->price;
                $order_detil->id = $v->id;
                $customize_x = array();
                foreach(json_decode($v->customize) as $key => $value)
                {
                    //return response()->json($value);
                    $customize_y = collect($product->customize)->where('id', $value->customize_id)->first();
                    //return response()->json($variant_y);
                    if($customize_y)
                    {
                        $customize_pilihan_collection = collect($customize_y->pilihan);
                        //return response()->json($sub_variant_collection);
                        $customize_pilihan = $customize_pilihan_collection->where('id', $value->pilihan_id)->first();
                        if($customize_pilihan)
                        {
                            $customize_z = [
                                'customize_id' => $customize_y->id,
                                'customize_name' => $customize_y->name,
                                'pilihan_id' => $customize_pilihan->id,
                                'pilihan_name' => $customize_pilihan->name,
                                'pilihan_price' => $customize_pilihan->price,
                            ];
                            $customize_x[] = $customize_z;
                            $order_detil->price += $customize_pilihan->price;
                        }
                    }
                }
                $order_detil->customize = json_encode($customize_x);
                $order_detil->qty = $v->qty;
                $order_detil->total_price = $order_detil->price * $v->qty;
                $order_detil->note = $v->note;

                $data[$i]->sub_total += $order_detil->total_price;
                
                $order_detil_many[] = $order_detil;
            }
            $data[$i]->fee = 0;
            $data[$i]->total = $data[$i]->sub_total + $data[$i]->fee + $data[$i]->service_fee;
            $data[$i]->detil = $order_detil_many;

        }

        return response()->json($data);
        //return response()->json(TrCartSavedResource::collection($data));
    }

    function CartDelete(Request $request) 
    {
        $data = TransOrder::whereIn('id',$request->id)
                ->where('tenant_id', '=', auth()->user()->tenant_id)
                ->where('order_type', '=', TransOrder::ORDER_TAVSIR) 
                ->where('status', '=', TransOrder::CART) 
                ->get();

        $deleteDetail = TransOrderDetil::whereIn('trans_order_id', $request->id)->delete();
        $data->each->delete();

        return response()->json($data);
    }

    
    function CartOrder(Request $request) 
    {
        try {
            DB::beginTransaction();
            $data = new TransOrder();
            if(!is_null($request->id)) {
                $data = TransOrder::find($request->id);
                $data->sub_total = 0;
            }
            else {
                $data->order_id = 'TRV-' . date('YmdHis');
            }
            
            $data->order_type = TransOrder::ORDER_TAVSIR;
            $data->status = TransOrder::CART;
            $data->casheer_id = auth()->user()->id;
            $data->tenant_id = $request->tenant_id;
            $data->business_id = $request->business_id;
            $data->merchant_id = $request->merchant_id;
            $data->sub_merchant_id = $request->sub_merchant_id;
            $data->is_save = $request->is_save;
            $data->save();

            $deleteDetail = TransOrderDetil::where('trans_order_id', $data->id)->delete();
            $order_detil_many = [];

            foreach ($request->product as $k => $v) 
            {
                $product = Product::find($v['product_id']);
                //dd($product);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->price = $product->price;
                $customize_x = array();
                foreach($v['customize'] as $key => $value)
                {   //CustomizeResource::collection($this->customize)
                    //dd($product->customize);
                    $customize_y = collect($product->customize)->where('id', $value)->first();
                    if($customize_y)
                    {
                        $customize_pilihan_collection = collect($customize_y->pilihan);
                        $customize_pilihan = $customize_pilihan_collection->where('id', $v['pilihan'][$key])->first();
                        if($customize_pilihan)
                        {
                            $customize_z = [
                                'customize_id' => $customize_y->id,
                                'customize_name' => $customize_y->name,
                                'pilihan_id' => $customize_pilihan->id,
                                'pilihan_name' => $customize_pilihan->name,
                                'pilihan_price' => $customize_pilihan->price,
                            ];
                            $customize_x[] = $customize_z;
                            $order_detil->price += $customize_pilihan->price;
                        }
                    }
                }
                //dd($customize_x);
                $order_detil->customize = json_encode($customize_x);
               // $order_detil->customize = $customize_x;
                $order_detil->qty = $v['qty'];
                $order_detil->total_price = $order_detil->price * $v['qty'];
                $order_detil->note = $v['note'];

                $data->sub_total += $order_detil->total_price;
                
                $order_detil_many[] = $order_detil;
            }
            $data->fee = 0;
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

    function orderConfirm(TsOrderConfirmRequest $request, $id)
    {
        $data = TransOrder::findOrfail($id);
        $data->detil->whereNotIn('id', $request->detil_id)->each(function ($item) {
            $item->delete();
        });
        $sum = $data->detil->sum('total_price');
        $data->sub_total = $sum;
        $data->total = $data->sub_total + $data->fee + $data->service_fee;
        $data->status = TransOrder::WAITING_PAYMENT;
        $data->save();

        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function PaymentMethod()
    {
        $data = PaymentMethod::get();
        return response()->json($data);
    }

    function OrderById($id) {
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
                    if($data->total > $request->cash)
                    {
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
                    $voucher = Voucher::where('hash', request()->voucher)->where('is_active', 1)->first();
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
                    $data->save();
                    break;
                
                default:
                    return response()->json(['error' => $payment_method->name.' Coming Soon'], 500);
                    break;
            }
            DB::commit();
            return response()->json( new TrOrderResource($data));
        }
        catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

}
