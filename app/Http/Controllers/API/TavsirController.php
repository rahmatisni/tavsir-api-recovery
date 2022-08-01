<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Tavsir\TrOrderRequest;
use App\Http\Resources\Tavsir\TrProductResource;
use App\Http\Resources\Tavsir\TrCartSavedResource;
use App\Http\Resources\Tavsir\TrOrderResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\PaymentMethod;

class TavsirController extends Controller
{
    function Product(Request $request)
    {
        $data = Product::when($filter = $request->filter, function($q)use ($filter){
            return $q->where('name', 'like', "%$filter%")
                    ->orwhere('sku', 'like', "%$filter%");
        })->when($category = $request->category, function($q)use ($category){
            return $q->where('category', '=', $category);
        });
        $data = $data->where('tenant_id', '=', auth()->user()->tenant_id)->get();

        return response()->json(TrProductResource::collection($data));
    }

    function Category() 
    {
        $data = Category::where('tenant_id', '=', auth()->user()->tenant_id)->select('name AS category')->orderBy('name')->get();

        return response()->json($data);
    }

    function CountNewTNG()
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
                    ->where('order_type', '=', TransOrder::ORDERTNG)       
                    ->count();

        return response()->json(['count' => $data]);
    }

    function CartById($id)
    {
        $data =DB::table('trans_order AS O') 
        ->where('id', '=', $id)
        ->selectRaw('O.id, O.order_id, O.sub_total, O.fee, O.service_fee, O.total, O.business_id, O.tenant_id,
            O.merchant_id, O.sub_merchant_id')
        ->first();

        $detail = DB::table('trans_order_detil')->where('trans_order_id', $data->id)->get();
        $order_detil_many = [];

        foreach ($detail as $k => $v) 
        {
            $product = Product::find($v->product_id);

            $order_detil = new TransOrderDetil();
            $order_detil->trans_order_id = $data->id;
            $order_detil->product_id = $product->id;
            $order_detil->product_name = $product->name;
            $order_detil->price = $product->price;
            $customize_x = array();
            foreach(json_decode($v->customize) as $key => $value)
            {
                $customize_y = collect($product->customize)->where('id', $value->customize_id)->first();
                if($customize_y)
                {
                    $customize_pilihan_collection = collect($customize_y->pilihan);
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

            $data->sub_total += $order_detil->total_price;
            
            $order_detil_many[] = $order_detil;
        }
        $data->fee = 0;
        $data->total = $data->sub_total + $data->fee + $data->service_fee;
        $data->detil = $order_detil_many;

        return response()->json($data);
    }

    function CountCarSaved()
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
                    ->where('order_type', '=', TransOrder::ORDERTAVSIR)       
                    ->where('status', '=', TransOrder::CART) 
                    ->where('is_save', '=', 1)
                    ->count();

        return response()->json(['count' => $data]);
    }

    function cartSaved()
    {

        $data =DB::table('trans_order AS O')
        ->where('tenant_id', '=', auth()->user()->tenant_id)
        ->where('order_type', '=', TransOrder::ORDERTAVSIR) 
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
                //dd($variant_x);
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
        //$data = TransOrder::find($request->id);
        $data = TransOrder::whereIn('id',$request->id)
                ->where('tenant_id', '=', auth()->user()->tenant_id)
                ->where('order_type', '=', TransOrder::ORDERTAVSIR) 
                ->where('status', '=', TransOrder::CART) 
                ->get();

        $deleteDetail = TransOrderDetil::whereIn('trans_order_id', $request->id)->delete();
        //DB::table('trans_order_detil')->where('trans_order_id', $data->id)->delete();
        $data->each->delete();

        return response()->json($data);
    }

    // function CartOrder(Request $request) 
    // {
    //     try {
    //         DB::beginTransaction();
    //         $data = new TransOrder();
    //         if(!is_null($request->id)) {
    //             $data = TransOrder::find($request->id);
    //             $data->sub_total = 0;
    //         }
    //         else {
    //             $data->order_id = 'TRV-' . date('YmdHis');
    //         }
            
    //         $data->order_type = TransOrder::ORDERTAVSIR;
    //         $data->status = TransOrder::CART;
    //         $data->tenant_id = $request->tenant_id;
    //         $data->business_id = $request->business_id;
    //         $data->merchant_id = $request->merchant_id;
    //         $data->sub_merchant_id = $request->sub_merchant_id;
    //         $data->is_save = $request->is_save;
    //         $data->save();

    //         $deleteDetail = TransOrderDetil::where('trans_order_id', $data->id)->delete();
    //         $order_detil_many = [];

    //         foreach ($request->product as $k => $v) 
    //         {
    //             $product = Product::find($v['product_id']);

    //             $order_detil = new TransOrderDetil();
    //             $order_detil->trans_order_id = $data->id;
    //             $order_detil->product_id = $product->id;
    //             $order_detil->product_name = $product->name;
    //             $order_detil->price = $product->price;
    //             $variant_x = array();
    //             foreach($v['variant'] as $key => $value)
    //             {
    //                 $variant_y = collect($product->variant)->where('id', $value)->first();
    //                 if($variant_y)
    //                 {
    //                     $sub_variant_collection = collect($variant_y->sub_variant);
    //                     $sub_variant = $sub_variant_collection->where('id', $v['sub_variant'][$key])->first();
    //                     if($sub_variant)
    //                     {
    //                         $variant_z = [
    //                             'variant_id' => $variant_y->id,
    //                             'variant_name' => $variant_y->name,
    //                             'sub_variant_id' => $sub_variant->id,
    //                             'sub_variant_name' => $sub_variant->name,
    //                             'sub_variant_price' => $sub_variant->price,
    //                         ];
    //                         $variant_x[] = $variant_z;
    //                         $order_detil->price += $sub_variant->price;
    //                     }
    //                 }
    //             }
    //             //dd($variant_x);
    //             $order_detil->variant = json_encode($variant_x);
    //             $order_detil->qty = $v['qty'];
    //             $order_detil->total_price = $order_detil->price * $v['qty'];
    //             $order_detil->note = $v['note'];

    //             $data->sub_total += $order_detil->total_price;
                
    //             $order_detil_many[] = $order_detil;
    //         }
    //         $data->fee = 0;
    //         $data->total = $data->sub_total + $data->fee + $data->service_fee;
    //         $data->save();
    //         $data->detil()->saveMany($order_detil_many);
    //         DB::commit();
    //         return response()->json(TransOrder::with('detil')->find($data->id));
    //     } catch (\Throwable $th) {
    //         DB::rollback();
    //         return response()->json(['error' => $th->getMessage()], 500);
    //     }

    // }
    
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
            
            $data->order_type = TransOrder::ORDERTAVSIR;
            $data->status = TransOrder::CART;
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


    function PaymentMethod()
    {
        $data = PaymentMethod::where('is_tavsir',1)->get();
        return response()->json($data);
    }

    function OrderById($id) {
        $data = TransOrder::findOrfail($id);
        return response()->json(TransOrder::with('detil')->find($data->id));
        //return response()->json(new TrOrderResource($data));
    }

    function Order(TrOrderRequest $request) 
    {
        try {
            DB::beginTransaction();

            
            $data = TransOrder::find($request->id);
            $data->sub_total = 0;
            $data->status = TransOrder::WAITING_PAYMENT;
            $data->is_save = 0;
            $data->save();

            $deleteDetail = TransOrderDetil::where('trans_order_id', $data->id)->delete();
            $order_detil_many = [];

            foreach ($request->product as $k => $v) 
            {
                $product = Product::find($v['product_id']);

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->price = $product->price;
                $customize_x = array();
                foreach($v['customize'] as $key => $value)
                {
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
                //dd($variant_x);
                $order_detil->customize = json_encode($customize_x);
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

    function PaymentOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            $data = TransOrder::find($request->id);
            $payment_method = PaymentMethod::findOrFail($request->payment_method_id);
            if ($payment_method->code_name == 'tunai')
            {
                if($request->total > $request->pay_amount)
                {
                    return response()->json(['message' => "Not Enough Balance"]);
                }
            }
            else{
                return response()->json(['error' => $payment_method->name.' Coming Soon'], 500);
            }

            $data->pay_amount = $request->pay_amount;
            $data->status = TransOrder::DONE;
            $data->save();
            DB::commit();
            return response()->json( new TrOrderResource($data));
        }
        catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

}
