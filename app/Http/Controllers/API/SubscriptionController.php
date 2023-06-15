<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Http\Requests\BusinessRequest;
use App\Http\Requests\DocumentSubscriptionRequest;
use App\Http\Requests\ExtendRequest;
use App\Http\Requests\SubscriptionChangeStatusRequest;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionCalculationResource;
use App\Http\Resources\SubscriptionDetilResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Jmrb;
use App\Models\PriceSubscription;
use App\Models\Subscription;
use Clockwork\Request\Request;
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
            'total_active' => $data->where('status', Subscription::AKTIF)->count(),
            'total_inactive' => $data->where('status', Subscription::TIDAK_AKTIF)->count(),
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
                $data->limit_cashier = 1;
                $data->limit_tenant = 1;
                $data->document_type = $request->document_type;
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

    public function extend($id, ExtendRequest $request)
    {
        $data = Subscription::findOrfail($id);
        $new_subscription = new Subscription();
        $new_subscription->id_activation = Str::lower(Str::random(10));
        $new_subscription->type = $data->type;
        $new_subscription->super_merchant_id = $data->super_merchant_id;
        $new_subscription->limit_tenant = $request->limit_tenant;
        $new_subscription->limit_cashier = $request->limit_cashier;
        $new_subscription->masa_aktif = $request->masa_aktif;
        $new_subscription->created_at = $request->aktif_awal;
        $new_subscription->save();

        return response()->json(new SubscriptionDetilResource($new_subscription));
    }

    public function price($id)
    {
        $data = Subscription::findOrfail($id);
        $price = PriceSubscription::first();
        $resource = (new SubscriptionCalculationResource($data))->price($price);
        return response()->json($resource);
    }

    public function document($id, DocumentSubscriptionRequest $request)
    {
        $data = Subscription::findOrfail($id);
        $data->file = $request->file;
        $data->document_type = $request->document_type;
        $data->detail_aktivasi = Subscription::MENUNGGU_KONFIRMASI;
        $data->save();

        return response()->json(new SubscriptionDetilResource($data));
    }
}
