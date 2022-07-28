<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\tavsir\TrOrderRequest;
use App\Http\Requests\tavsir\TnGOrderVerif;
use App\Http\Resources\Tavsir\TrProductResource;
use App\Http\Resources\Tavsir\TrCartSavedResource;
use App\Http\Resources\Tavsir\TrOrderResource;
use App\Http\Resources\Tavsir\TnGOrderResource;
use App\Models\Product;
use App\Models\Category;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;

class TavsirTnGController extends Controller
{
    public function TenantOrder(Request $request) 
    {
        $data = TransOrder::with('detil')->when($order_id = $request->order_id, function($q)use ($order_id){
            return $q->where('order_id', 'like', "%$order_id%");
        });
          
        $data = $data->where('tenant_id', '=', auth()->user()->tenant_id)
                    ->where('order_type', '=', TransOrder::ORDERTNG)
                    ->whereIn('status',[TransOrder::PENDING, TransOrder::WAITING_CONFIRMATION, 
                        TransOrder::WAITING_PAYMENT, TransOrder::PREPARED, TransOrder::READY])  
                    ->get();

        return response()->json(TnGOrderResource::collection($data));

    }

    public function TenantOrderDetail(Request $request) 
    {
        $data = TransOrder::with('detil')->when($order_id = $request->order_id, function($q)use ($order_id){
            return $q->where('order_id', 'like', "%$order_id%");
        });
          
        $data = $data->where('tenant_id', '=', auth()->user()->tenant_id)
                    ->where('order_type', '=', TransOrder::ORDERTNG)
                    ->whereIn('status',[TransOrder::PENDING, TransOrder::WAITING_CONFIRMATION, 
                        TransOrder::WAITING_PAYMENT, TransOrder::PREPARED, TransOrder::READY])  
                    ->get();

        return response()->json(TnGOrderResource::collection($data));

    }

    function OrderReady($id)
    {
        $data = TransOrder::findOrfail($id);
        $data->status = TransOrder::READY;
        $data->save();
        return response()->json(new TrOrderResource($data));
        //return response()->json(new TnGOrderResource($data));
    }

    function OrderVerification(TnGOrderVerif $request, $id)
    {
        $data = TransOrder::findOrfail($id);
        if($data->customer_id == $request->customer_id)
        {
            if ($data->code_verif == $request->code_verif) {
                $data->status = TransOrder::READY;
                $data->save();
                return response()->json(new TrOrderResource($data));
            }
            else {
                return response()->json(['message' => "Wrong Code Verification"]);
            }

        }
        else {
            return response()->json(['message' => "Not Valid Customer"]);
        }
    }


}
