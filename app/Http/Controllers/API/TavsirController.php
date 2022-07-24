<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\TrOrderRequest;
use App\Http\Resources\Tavsir\TrProductResource;
use App\Http\Resources\Tavsir\TrCartSavedResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;

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

    function cartSaved(Request $request)
    {
        $data = TransOrder::where('tenant_id', '=', auth()->user()->tenant_id)
                    ->where('order_type', '=', TransOrder::ORDERTAVSIR) 
                    ->where('status', '=', TransOrder::CART) 
                    ->where('is_save', '=', 1)
                    ->get();

        return response()->json(TrCartSavedResource::collection($data));
    }

    function CartOrder(Request $request) 
    {
        try {
            DB::beginTransaction();
            $data = new TransOrder();
            if(!is_null($request->id)) {
                $data = TransOrder::find($request->id);
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

                $order_detil = new TransOrderDetil();
                $order_detil->trans_order_id = $data->id;
                $order_detil->product_id = $product->id;
                $order_detil->product_name = $product->name;
                $order_detil->price = $product->price;
                $variant_x = array();
                foreach($v['variant'] as $key => $value)
                {
                    $variant_y = collect($product->variant)->where('id', $value)->first();
                    if($variant_y)
                    {
                        $sub_variant_collection = collect($variant_y->sub_variant);
                        $sub_variant = $sub_variant_collection->where('id', $v['sub_variant'][$key])->first();
                        if($sub_variant)
                        {
                            $variant_z = [
                                'variant_id' => $variant_y->id,
                                'variant_name' => $variant_y->name,
                                'sub_variant_name' => $sub_variant->name,
                                'sub_variant_price' => $sub_variant->price,
                            ];
                            $variant_x[] = $variant_z;
                            $order_detil->price += $sub_variant->price;
                        }
                    }
                }
                //dd($variant_x);
                $order_detil->variant = json_encode($variant_x);
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

    function Order(TrOrderRequest $request) 
    {
        try {
            DB::beginTransaction();

            
            $data = TransOrder::find($request->id);
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
                $variant_x = array();
                foreach($v['variant'] as $key => $value)
                {
                    $variant_y = collect($product->variant)->where('id', $value)->first();
                    if($variant_y)
                    {
                        $sub_variant_collection = collect($variant_y->sub_variant);
                        $sub_variant = $sub_variant_collection->where('id', $v['sub_variant'][$key])->first();
                        if($sub_variant)
                        {
                            $variant_z = [
                                'variant_id' => $variant_y->id,
                                'variant_name' => $variant_y->name,
                                'sub_variant_name' => $sub_variant->name,
                                'sub_variant_price' => $sub_variant->price,
                            ];
                            $variant_x[] = $variant_z;
                            $order_detil->price += $sub_variant->price;
                        }
                    }
                }
                //dd($variant_x);
                $order_detil->variant = json_encode($variant_x);
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

}
