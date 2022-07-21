<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TravShop\TsProducDetiltResource;
use App\Http\Resources\TravShop\TsProductResource;
use App\Http\Resources\TravShop\TsTenantResource;
use App\Http\Resources\TravShop\TsRestAreaResource;
use App\Models\Product;
use App\Models\RestArea;
use App\Models\Tenant;

class TravShopController extends Controller
{
    function restArea(Request $request)
    {
        $data = RestArea::when($name = $request->name, function($q)use ($name){
            return $q->where('name', 'like', "%$name%");
        });
        $data = $data->get();

        if($request->lat && $request->lon){
            $data = $data->filter(function($item) use ($request){
                return $this->haversine($item->latitude, $item->longitude, $request->lat, $request->lon, $request->distance ?? 1);
            });
        }

        return response()->json(TsRestAreaResource::collection($data));
    }

    function tenant(Request $request)
    {
        $data = Tenant::when($rest_area_id = $request->rest_area_id, function ($q) use ($rest_area_id) {
                            return $q->where('rest_area_id', $rest_area_id);
                        })->when($name = $request->name, function($q)use ($name){
                            return $q->where('name', 'like', "%$name%");
                        })->when($product = $request->product, function($q)use ($product){
                            return $q->whereHas('product', function($q)use ($product){
                                return $q->where('name', 'like', "%$product%");
                            });
                        })->get();

        return response()->json(TsTenantResource::collection($data));
    }

    function product(Request $request)
    {
        $data = Product::when($name = $request->name, function($q)use ($name){
                            return $q->where('name', 'like', "%$name%");
                        })->when($tenant_id = $request->tenant_id, function($q)use ($tenant_id){
                            return $q->where('tenant_id', $tenant_id);
                        })->when($category = $request->category, function($q)use ($category){
                            return $q->where('category', $category);
                        })->get();
        
        return response()->json(TsProductResource::collection($data));
    }

    function productById($id)
    {
        $data = Product::findOrfail($id);
        return response()->json(new TsProducDetiltResource($data));
    }
}
