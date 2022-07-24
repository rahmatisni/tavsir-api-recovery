<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TsOrderConfirmRequest;
use App\Http\Requests\TsOrderRequest;
use App\Http\Resources\TravShop\TsOrderResource;
use App\Http\Resources\TravShop\TsProducDetiltResource;
use App\Http\Resources\TravShop\TsProductResource;
use App\Http\Resources\TravShop\TsTenantResource;
use App\Http\Resources\TravShop\TsRestAreaResource;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\RestArea;
use App\Models\Tenant;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use Illuminate\Support\Facades\DB;

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
        })->when($category = $request->category, function ($q) use ($category) {
            return $q->where('category', $category);
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
            $data->order_id = 'TNG-' . date('YmdHis');
            $data->tenant_id = $request->tenant_id;
            $data->business_id = $request->business_id;
            $data->customer_id = $request->customer_id;
            $data->merchant_id = $request->merchant_id;
            $data->sub_merchant_id = $request->sub_merchant_id;
            $order_detil_many = [];
            $data->save();

            // dd($request->all());

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
                $order_detil->variant = json_encode($variant_x);
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
            DB::commit();
            return response()->json(TransOrder::with('detil')->find($data->id));
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    function orderById($id)
    {
        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function orderConfirm(TsOrderConfirmRequest $request, $id)
    {
        $data = TransOrder::findOrfail($id);
        $data->detil->whereNotIn('id', $request->detil_id)->each(function ($item) {
            $item->delete();
        });
        $data->status = TransOrder::WAITING_PAYMENT;
        $data->save();

        $data = TransOrder::findOrfail($id);
        return response()->json(new TsOrderResource($data));
    }

    function paymentMethod()
    {
        $data = PaymentMethod::all();
        return response()->json($data);   
    }
}
