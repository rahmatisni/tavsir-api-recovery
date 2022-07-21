<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TravShop\TsTenantByRestAreaResource;
use App\Http\Resources\TravShop\TsRestAreaResource;
use App\Models\RestArea;
use App\Models\Tenant;

class TravShopController extends Controller
{
    function restArea()
    {
        $data = RestArea::when($name = request()->name, function($q)use ($name){
            return $q->where('name', 'like', "%$name%");
        });
        $data = $data->get();

        if(request()->lat && request()->lon && !request()->name){
            $data = $data->filter(function($item){
                return $this->haversine($item->latitude, $item->longitude, request()->lat, request()->lon, request()->distance ?? 1);
            });
        }

        return response()->json(TsRestAreaResource::collection($data));
    }

    function tenantByRestarea($id)
    {
        $data = Tenant::where('rest_area_id', $id)->get();
        return response()->json(TsTenantByRestAreaResource::collection($data)->collection->groupBy('category'));
    }
}
