<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentSubscriptionRequest;
use App\Http\Requests\ExtendRequest;
use App\Http\Requests\KuotaKasirTenantRequest;
use App\Http\Requests\MapingSubscriptionRequest;
use App\Http\Requests\MapingSubscriptionTenantRequest;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\CashierTenantResource;
use App\Http\Resources\MemberTenantResource;
use App\Http\Resources\SubscriptionCalculationResource;
use App\Http\Resources\SubscriptionDetilResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Business;
use App\Models\PriceSubscription;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TransOperational;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:' . User::TENANT . ',' . User::OWNER)->only('maapingSubscriptionKasir', 'showKasirTenant');
        // $this->middleware('role:' . User::TENANT . ',' . User::OWNER)->only('maapingSubscriptionKasir');
        $this->middleware('role:' . User::OWNER)->only('showMemberTenantOwner', 'kuotaKasirTenant');
        // $this->middleware('role:' . User::TENANT)->only('showKasirTenant');
        $this->middleware('role:' . User::SUPERADMIN)->only('aktivasi');
    }

    public function index()
    {
        $queryOrder = "CASE WHEN detail_aktivasi = 'MENUNGGU KONFIRMASI' THEN 1 ";
        $queryOrder .= "WHEN detail_aktivasi = 'TERKONFIRMASI' THEN 2 ";
        $queryOrder .= "WHEN detail_aktivasi = 'DITOLAK' THEN 3 ";
        $queryOrder .= "ELSE 3 END";

        $data = Subscription::when($business_id = request()->business_id, function ($q) use ($business_id) {
            return $q->where('business_id', $business_id);
        })
            ->mySortOrder(request())
            ->orderByRaw($queryOrder)
            ->get();

        $record = [
            'total_data' => $data->count(),
            'total_active' => $data->where('status_aktivasi', Subscription::AKTIF)->count(),
            'total_inactive' => $data->where('status_aktivasi', Subscription::TIDAK_AKTIF)->count(),

            'total_aktivasi_terkonfirimasi' => $data->where('detail_aktivasi', Subscription::TERKONFIRMASI)->count(),
            'total_aktivasi_waiting_konfirmasi' => $data->where('detail_aktivasi', Subscription::MENUNGGU_KONFIRMASI)->count(),
            'total_aktivasi_ditolak' => $data->where('detail_aktivasi', Subscription::DITOLAK)->count(),
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
            $data->detail_aktivasi = Subscription::MENUNGGU_KONFIRMASI;
            $data->save();

            $business = Business::find($request->super_merchant_id);
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
        $data = Tenant::byOwner()->get();
        $kuota_tenant = $limit->where('status_aktivasi', Subscription::AKTIF)->sum('limit_tenant');
        $tenant_aktif = $data->where('is_subscription', 1)->count();

        $kuota_kasir = $limit->where('status_aktivasi', Subscription::AKTIF)->sum('limit_cashier');
        $kasir_aktif = User::where('role', User::CASHIER)->where('is_subscription', 1)->whereIn('tenant_id', $data->pluck('id')->toArray())->count();
        $all_limit_kasir_tneant = $data->sum('kuota_kasir');

        $result = [
            'kuota_tenant' => $kuota_tenant,
            'tenant_aktif' => $tenant_aktif,
            'tenant_belum_terpakai' => $kuota_tenant - $tenant_aktif,
            'kasir_teraktivasi' => $all_limit_kasir_tneant,
            'limit_kasir' => $kuota_kasir - $all_limit_kasir_tneant,
            'kuota_kasir' => $kuota_kasir,
            'kasir_aktif' => $kasir_aktif,
            'kasir_belum_terpakai' => $kuota_kasir - $kasir_aktif,
            'tenant' => MemberTenantResource::collection($data)
        ];
        return response()->json($result);

    }

    public function aktivasi($id, Request $request)
    {
        $data = Subscription::where('id', $id)->first();
        if (!$data) {
            return response()->json(['message' => 'Subscription invalid'], 422);
        }

        if ($data->detail_aktivasi != Subscription::MENUNGGU_KONFIRMASI) {
            return response()->json(['message' => 'Subscription status not ' . Subscription::MENUNGGU_KONFIRMASI], 422);
        }

        try {
            DB::beginTransaction();

            $data->start_date = Carbon::now();
            $data->detail_aktivasi = Subscription::TERKONFIRMASI;
            $data->note = $request->note;
            $data->superMerchant;
            $data->save();
            $superMerchant = $data->superMerchant;
            if ($superMerchant->subscription_end) {
                //Jika Expire
                $subscription_last = Carbon::parse($superMerchant->subscription_end);
                if ($subscription_last->lt(Carbon::now()->subDay())) {
                    $superMerchant->subscription_end = Carbon::now()->addMonth($data->masa_aktif);
                } else {
                    //Belum expire
                    $superMerchant->subscription_end = $subscription_last->addMonths($data->masa_aktif);
                }
            } else {
                //Belum pernah subscription
                $superMerchant->subscription_end = Carbon::now()->addMonth($data->masa_aktif);
            }

            $superMerchant->save();
            DB::commit();
            return response()->json(['message' => 'Subscription aktif ' . $data->aktif_awal]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['message' => $th->getMessage()], 422);
        }
    }

    public function reject($id, Request $request)
    {
        $data = Subscription::whereNull('start_date')->where('id', $id)->first();
        if (!$data) {
            return response()->json(['message' => 'Subscription invalid'], 422);
        }
        if ($data->detail_aktivasi == Subscription::TERKONFIRMASI) {
            return response()->json(['message' => 'Subscription tidak bnisa di tolak karena sudah dikonfirmasi'], 422);
        }
        $data->detail_aktivasi = Subscription::DITOLAK;
        $data->note = $request->note;
        $data->save();
        return response()->json(['message' => 'Subscription reject ']);
    }


    public function showKasirTenant($id = null)
    {
        if ($id === null) {
            $id = null;
        }

        if (auth()->user()->role == 'OWNER' && $id) {
            $limit = Subscription::byOwner(auth()->user()->business_id)->get();
            $data = User::where('role', User::CASHIER)->where('tenant_id', $id)->get();
            $tenant_id = $id;
        } else {
            $limit = Subscription::byTenant(auth()->user()->tenant_id)->get();
            // dd(auth()->user());
            $data = User::where('role', User::CASHIER)->where('tenant_id', auth()->user()->tenant_id)->get();
            $tenant_id = auth()->user()->tenant_id;
        }
        $tenant = Tenant::findOrfail($tenant_id);
        $kuota_aktif = $data->where('is_subscription', 1)->count();
        $kuota_belum_terpakai = ($tenant->kuota_kasir ?? 0) - $kuota_aktif;
        $result = [
            'tenant_name' => $tenant->name,
            'total_kasir' => $limit->where('status_aktivasi', Subscription::AKTIF)->sum('limit_cashier'),
            'kuota_kasir' => $tenant->kuota_kasir ?? 0,
            'kuota_aktif' => $kuota_aktif,
            'kuota_belum_terpakai' => $kuota_belum_terpakai,
            'cashier' => CashierTenantResource::collection($data),
        ];
        return response()->json($result);

    }

    public function kuotaKasirTenant(KuotaKasirTenantRequest $request)
    {
        $tenant = Tenant::findOrfail($request->tenant_id);
        $tenant->kuota_kasir = $request->kuota_kasir;
        $tenant->save();
        return response()->json(['message' => true]);
    }

    public function maapingSubscriptionTenant(MapingSubscriptionTenantRequest $request)
    {
        $tenant = Tenant::byOwner()->get();
        $tenant_has_subscription = $tenant->where('is_subscription', 1)->count();
        $aktivasi_tenant = $tenant->where('id', $request->id)->first();
        if (!$aktivasi_tenant) {
            return response()->json(['message' => 'Tenant invalid'], 422);
        }
        if ($request->status == 'false') {
            //Cek toko open
            $is_tenant_open = TransOperational::where('tenant_id', $request->id)->whereNull('end_date')->count();
            if ($is_tenant_open > 0) {
                helperThrowErrorvalidation(['id' => 'Tenant ini terdapat ' . $is_tenant_open . ' toko yang beroperasi']);
            }
            $aktivasi_tenant->is_subscription = 0;
            $aktivasi_tenant->kuota_kasir = 0;
            $aktivasi_tenant->save();
            foreach ($aktivasi_tenant->cashear as $value) {
                $value->is_subscription = 0;
                $value->save();
            }
            return response()->json(['message' => 'Unsubscription success']);
        }
        $kuota = Subscription::byOwner()->get()->where('status_aktivasi', Subscription::AKTIF)->sum('limit_tenant');
        if ($kuota <= $tenant_has_subscription) {
            return response()->json(['message' => 'Kutoa tenant hanya '.$kuota.'. Sisa limit tenant adalah '.($kuota - $tenant_has_subscription)], 422);
        }

        $aktivasi_tenant->is_subscription = 1;
        $aktivasi_tenant->save();

        return response()->json(['message' => 'Subscription success']);
    }

    public function maapingSubscriptionKasir(MapingSubscriptionRequest $request)
    {
        if (auth()->user()->role == 'OWNER') {
            $kasirAll = User::where('tenant_id', $request->tenant_id)->where('role', User::CASHIER)->get();

        } else {
            $kasirAll = User::where('tenant_id', auth()->user()->tenant_id)->where('role', User::CASHIER)->get();

        }
        // $kasirAll = User::where('tenant_id', auth()->user()->tenant_id)->where('role',User::CASHIER)->get();
        $kasir = $kasirAll->where('id', $request->id)->first();

        $tenant = $kasir?->tenant;
        if (!$tenant) {
            return response()->json(['message' => 'Tenant invalid'], 422);
        }
        if ($request->status == 'false') {
            $is_tenant_kasir_open = TransOperational::where('tenant_id', $kasir->tenant_id)
                ->where('casheer_id', $kasir->id)
                ->whereNull('end_date')
                ->count();

            if($is_tenant_kasir_open > 0){
                helperThrowErrorvalidation(['id' => 'Kasir sedang beroperasi']);
            }
            $user = User::where('id', $kasir->id)->first();
            // dd($user);

            if ($user->fcm_token) {
                $payload = array(
                    'type' => 'click',
                    'action' => 'relogin',
                );
                sendNotif($user->fcm_token, '❗Anda telah keluar dari Getpay❗', 'Lisensi anda telah dinonaktifkan!', $payload);
            }

            $user->accessTokens()->delete();

            $kasir->is_subscription = 0;
            $kasir->save();
            return response()->json(['message' => 'Unsubscription success']);
        }

        $kuota = $tenant->kuota_kasir;
        $kasir_subscrption = $kasirAll->where('is_subscription', 1)->count();
        $sisa = $kuota - $kasir_subscrption;
        if ($kuota <= $kasir_subscrption) {
            return response()->json(['message' => 'Kuota Kasir ' . $kuota . ' sisa ' . $sisa], 422);
        }

        $kasir->is_subscription = 1;
        $kasir->save();

        return response()->json(['message' => 'Subscription success']);
    }

}