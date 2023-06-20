<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentSubscriptionRequest;
use App\Http\Requests\ExtendRequest;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\CashierTenantResource;
use App\Http\Resources\MemberTenantResource;
use App\Http\Resources\SubscriptionCalculationResource;
use App\Http\Resources\SubscriptionDetilResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\PriceSubscription;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function __construct()
    {
    }

    public function index()
    {
        $data = Subscription::when($business_id = request()->business_id, function ($q) use ($business_id) {
            return $q->where('business_id', $business_id);
        })->get();
        $record = [
            'total_data' => $data->count(),
            'total_active' => $data->where('status_aktivasi', Subscription::AKTIF)->count(),
            'total_inactive' => $data->where('status_aktivasi', Subscription::WAITING_ACTIVATION)->count(),
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
            $data->super_merchant_id = $request->business_id;
            $data->masa_aktif = $request->masa_aktif;
            $data->limit_cashier = 1;
            $data->limit_tenant = 1;
            $data->document_type = $request->document_type;
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
        $new_subscription->detail_aktivasi = Subscription::MENUNGGU_KONFIRMASI;
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

    public function showMemberTenantOwner()
    {
        $limit = Subscription::byOwner()->get();
        $data =  Tenant::byOwner()->get();
        $result = [
            'limit_tenant' => $limit->where('status_aktivasi', Subscription::AKTIF)->sum('limit_tenant'),
            'limit_kasir' => $limit->where('status_aktivasi', Subscription::AKTIF)->sum('limit_cashier'),
            'tenant' => MemberTenantResource::collection($data)
        ];
        return response()->json($result);

    }

    public function maapingSubscription($tenant_id)
    {
        $tenant = Tenant::byOwner()->get();
        $tenant_has_subscription = $tenant->where('is_subscription', 1)->count();
        $aktivasi_tenant = $tenant->where('id', $tenant_id)->first();
        if(!$aktivasi_tenant){
            return  response()->json(['message' => 'Tenant not found'], 422);
        }
        $kuota = Subscription::byOwner()->get()->where('status_aktivasi', Subscription::AKTIF)->sum('limit_tenant');
        if($kuota <= $tenant_has_subscription){
            return  response()->json(['message' => 'Limit tenant tidak tersedia'], 422);
        }

        $aktivasi_tenant->is_subscription = 1;
        $aktivasi_tenant->save();

        return  response()->json(['message' => 'Subscription success']);
    }


    public function aktivasi($id)
    {
        $data = Subscription::whereNull('start_date')->where('id',$id)->first();
        if(!$data){
            return  response()->json(['message' => 'Subscription not found'], 422);
        }
        $data->start_date = Carbon::now();
        $data->save();
        return response()->json(['message' => 'Subscription aktif '.$data->aktif_awal]);
    }


    public function showKasirTenant()
    {
        if(auth()->user()->role != User::TENANT){
            return  response()->json(['message' => 'Permission Denied'], 403);
        }
        $limit = Subscription::byOwner(auth()->user()->tenant->business_id)->get();

        $data = User::where('role', User::CASHIER)->where('tenant_id', auth()->user()->tenant_id)->get();
        $result = [
            'limit_kasir' => $limit->where('status_aktivasi', Subscription::AKTIF)->sum('limit_cashier'),
            'cashier' => CashierTenantResource::collection($data)
        ];
        return response()->json($result);

    }

}
