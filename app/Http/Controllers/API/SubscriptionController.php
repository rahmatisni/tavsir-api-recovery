<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Http\Requests\BusinessRequest;
use App\Http\Requests\SubscriptionChangeStatusRequest;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionDetilResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Jmrb;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function index()
    {
        $data = Subscription::when($business_id = request()->business_id, function ($q) use ($business_id) {
            return $q->where('business_id', $business_id);
        })->get();
        $record = [
            'total_data' => $data->count(),
            'total_active' => $data->where('status', Subscription::ACTIVE)->count(),
            'total_inactive' => $data->where('status', Subscription::INACTIVE)->count(),
            'detil' => SubscriptionResource::collection($data)
        ];
        return response()->json($record);
    }

    public function store(SubscriptionRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new Subscription();
            $data->id_activation = Str::lower(Str::random(10));
            $data->type = $request->type;
            $data->file = $request->file;
            if ($request->type == Subscription::JMRB) {
                $jmrb = new Jmrb();
                $jmrb->pic = $request->pic;
                $jmrb->phone = $request->phone;
                $jmrb->hp = $request->hp;
                $jmrb->email = $request->email;
                $jmrb->save();
                $data->super_merchant_id = $jmrb->id;
                $data->masa_aktif = $request->masa_aktif;
                $data->created_at = $request->aktif_awal;
            }

            if ($request->type == Subscription::OWNER) {
                $data->super_merchant_id = $request->business_id;
                $data->masa_aktif = $request->masa_aktif;
                $data->limit_cashier = $request->limit_cashier;
                $data->created_at = $request->aktif_awal;
            }
            $data->save();
            DB::commit();
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $data = Subscription::findOrfail($id);
        return response()->json(new SubscriptionDetilResource($data));
    }

    public function update(SubscriptionRequest $request, $id)
    {
        $data = Subscription::findOrfail($id);
        $data->fill($request->all());
        $data->save();
        return response()->json($data);
    }

    public function destroy($id)
    {
        $data = Subscription::findOrfail($id);
        $data->delete();
        return response()->noContent();
    }
}
