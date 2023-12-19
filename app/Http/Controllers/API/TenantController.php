<?php

namespace App\Http\Controllers\API;

use App\Exports\TenantExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\BukaTutupTokoRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\TransSaldo;
use App\Models\TransOperational;
use Carbon\Carbon;
use DB;
use Maatwebsite\Excel\Facades\Excel;


class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // $filter = $request?->filter;
        $filter = $request?->except('filter.in_selforder');
        $filter = $request?->except('filter.in_takengo');

        $filterLike = $request?->filterlike;
        $filterLikeas = $request?->filteras;
        $businessStatus = $request?->filter['status_perusahaan'] ?? false;
        $SOStatus = ($request?->filter['in_selforder'] ?? false) === false ? false: (int)$request?->filter['in_selforder'];
        $TNGStatus =($request?->filter['in_takengo'] ?? false) === false ? false:(int)$request?->filter['in_takengo'];
        $restAreaID = ($request?->filter['rest_area_id'] ?? false) === false ? false: (int)$request?->filter['rest_area_id'];
        $categoryID = ($request?->filter['category_tenant_id'] ?? false) === false ? false: (int)$request?->filter['category_tenant_id'];

        $data = Tenant::with('business', 'rest_area', 'ruas', 'order', 'category_tenant')->myWheres($filter)->myWhereLikeStartCol($filterLike)
        ->myWhereLikeCol($filterLikeas)
        ->when($businessStatus != false, function ($query) use ($businessStatus) {
            // Adding the filter for business.status_perusahaan
            $query->whereHas('business', function ($businessQuery) use ($businessStatus) {
                $businessQuery->where('status_perusahaan', $businessStatus);
            });
        })
        ->when($SOStatus != false , function ($query) use ($SOStatus) {
            $query->where('in_selforder' , $SOStatus);
        })
        ->when($TNGStatus != false , function ($query) use ($TNGStatus) {
            $query->where('in_takengo', $TNGStatus);
        })
         ->when($restAreaID != false , function ($query) use ($restAreaID) {
            $query->where('rest_area_id', $restAreaID);
        }) ->when($categoryID != false , function ($query) use ($categoryID) {
            $query->where('category_tenant_id', $categoryID);
        })
        ->when(auth()->user()->role === 'OWNER', function ($query) use ($TNGStatus) {
            $query->where('business_id',auth()->user()->business_id);
        })
        ->when(auth()->user()->role === 'AREA', function ($query) use ($TNGStatus) {
            $query->where('rest_area_id',auth()->user()->rest_area_id);
        })
        ->get();
        return response()->json(TenantResource::collection($data));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTenantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TenantRequest $request)
    {
        $data = new Tenant();
        $data->fill($request->validated());
        $data->save();

        $saldo = new TransSaldo();
        $saldo->rest_area_id = $data->rest_area_id;
        $data->saldo()->save($saldo);

        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */
    public function show(Tenant $tenant)
    {
        return response()->json(new TenantResource($tenant));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTenantRequest  $request
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */
    public function update(TenantRequest $request, Tenant $tenant)
    {
        $tenant->fill($request->all());
        $tenant->save();
        return response()->json($tenant);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTenantRequest  $request
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */

    public function setPayment(Request $request, Tenant $tenant)
    {

        $paymentMethods = PaymentMethod::all();
        $ids = [];

        foreach ($paymentMethods as $value) {
            $ids[] = $value->id;
        }
        if (!in_array($request->list_payment, $ids)) {
            return response()->json(["status" => 'Failed', 'role' => auth()->user()->role, 'message' => 'ID Pembayaran Tidak Dikenali'], 422);
        }
        if (in_array(auth()->user()->role, [User::TENANT, User::OWNER])) {
            $tenant = auth()->user()->role === User::TENANT ? Tenant::find(auth()->user()->tenant_id) : Tenant::where('business_id', auth()->user()->business_id)->where('id', $request->tenant_id)->firstOrFail();
            $bucket_payment = json_decode($tenant->list_payment_bucket);
            $tenant_payment = json_decode($tenant->list_payment);
            if (!in_array($request->list_payment, $bucket_payment)) {
                return response()->json(["status" => 'Failed', 'role' => auth()->user()->role, 'message' => 'ID Pembayaran Tidak Dalam Daftar'], 422);
            }
            if (!$tenant_payment) {
                $tenant->update(['list_payment' => [(int) $request->list_payment]]);
                return response()->json(["status" => 'success', 'role' => auth()->user()->role, 'message' => 'Setting Payment Berhasil Diaktifkan'], 200);
            }
            if (($key = array_search($request->list_payment, $tenant_payment)) !== false) {
                array_splice($tenant_payment, array_search($request->list_payment, $tenant_payment), 1);
                $tenant->update(['list_payment' => $tenant_payment]);
                return response()->json(["status" => 'success', 'role' => auth()->user()->role, 'message' => 'Setting Payment Berhasil Dinonaktifkan'], 200);
            } else {
                array_push($tenant_payment, (int) $request->list_payment);
                $tenant->update(['list_payment' => $tenant_payment]);
                return response()->json(["status" => 'success', 'role' => auth()->user()->role, 'message' => 'Setting Payment Berhasil Diaktifkan'], 200);
            }

        }
        if (in_array(auth()->user()->role, [User::SUPERADMIN, User::ADMIN])) {
            if (!$request->tenant_id) {
                return response()->json(["status" => 'Failed', 'role' => 'UNKNOWN', 'message' => 'No Tenant Requested'], 422);
            }
            $tenant = Tenant::find($request->tenant_id);
            $bucket_payment = json_decode($tenant->list_payment_bucket);
            $tenant_payment = json_decode($tenant->list_payment);
            if (!$bucket_payment) {
                $tenant->update(['list_payment_bucket' => [(int) $request->list_payment]]);
                return response()->json(["status" => 'success', 'role' => auth()->user()->role, 'message' => 'Setting Payment Berhasil Didaftarkan'], 200);
            }
            if (($key = array_search($request->list_payment, $bucket_payment)) !== false) {
                array_splice($bucket_payment, array_search($request->list_payment, $bucket_payment), 1);
                if ($tenant_payment) {
                    if (($key = array_search($request->list_payment, $tenant_payment)) !== false) {
                        array_splice($tenant_payment, array_search($request->list_payment, $tenant_payment), 1);
                        $tenant->update(['list_payment' => $tenant_payment]);
                    }
                }
                $tenant->update(['list_payment_bucket' => $bucket_payment]);
                return response()->json(["status" => 'success', 'role' => auth()->user()->role, 'message' => 'Setting Payment Berhasil Dinonaktifkan'], 200);
            } else {
                array_push($bucket_payment, (int) $request->list_payment);
                $tenant->update(['list_payment_bucket' => $bucket_payment]);
                return response()->json(["status" => 'success', 'role' => auth()->user()->role, 'message' => 'Setting Payment Berhasil Didaftarkan'], 200);
            }

        }
        return response()->json(["status" => 'Failed', 'role' => 'UNKNOWN', 'message' => 'DONT TRY'], 422);
    }

    public function setFeature(Request $request, Tenant $tenant)
{
    $tenant = Tenant::where('id', $request->tenant_id)->firstOrFail();
    try {
        if ($request->url_self_order) {
            $validator = Validator::make($request->all(), [
                'url_self_order' => 'nullable|url',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' =>  "The url self order must be a valid URL"], 422);
            }
            $tenant->update(['url_self_order' => $request->url_self_order]);

        
        }
        if (in_array(auth()->user()->role, [User::SUPERADMIN, User::ADMIN])) {
            $tenant->update(array_map('intval', $request->except(['url_self_order'])));

            return response()->json([
                "status" => 'Success',
                'role' => '-',
                'data' =>
                    [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'in_takengo' => $tenant->in_takengo,
                        'in_selforder' => $tenant->in_selforder,
                        'is_scan' => $tenant->is_scan,
                        'is_print' => $tenant->is_print,
                        'is_composite' => $tenant->is_composite,
                        'url_self_order' => $tenant->url_self_order
                    ]
            ], 200);
        }
        return response()->json(["status" => 'Failed', 'role' => 'UNKNOWN', 'message' => 'DONT TRY'], 422);

    } catch (\Throwable $th) {
        return response()->json($th->getMessage(), 500);
    }
}

    public function sawFeature(Request $request, Tenant $tenant)
    {
        $tenant = Tenant::where('id', $request->tenant_id)->firstOrFail();
        return response()->json([
            "status" => 'Success',
            'role' => '-',
            'data' =>
                [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'in_takengo' => $tenant?->in_takengo ?? 0,
                    'in_selforder' => $tenant?->in_selforder ?? 0,
                    'is_scan' => $tenant->is_scan ?? 0,
                    'is_print' => $tenant->is_print ?? 0,
                    'is_composite' => $tenant->is_composite ?? 0,
                    'url_self_order' => $tenant->url_self_order

                ]
        ], 200);
    }

    public function bukaTutupToko(BukaTutupTokoRequest $request)
    {

        $user = auth()->user();

        $tenant = Tenant::find($request->id);

        if ($request->is_open == '1') {
            $cek = TransOperational::where('tenant_id', $user->tenant_id)
                ->whereDay('start_date', '=', date('d'))
                ->whereMonth('start_date', '=', date('m'))
                ->whereYear('start_date', '=', date('Y'))
                ->whereNull('end_date')
                ->get();

            if ($cek->count() <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Belum ada periode berjalan, silahkan buka kasir otomatis toko terbuka'
                ], 422);
            }
        } else
            if ($request->is_open == '0') {
                $data = User::where([['id', '!=', $user->id], ['tenant_id', $user->tenant_id]])->get();
                $ids = array();
                foreach ($data as $val) {
                    if ($val['fcm_token'] != null && $val['fcm_token'] != '')
                        array_push($ids, $val['fcm_token']);
                }

                if ($ids != '') {
                    $payload = array(
                        'id' => random_int(1000, 9999),
                        'type' => 'action',
                        'action' => 'refresh_buka_toko'
                    );
                    $result = sendNotif($ids, 'Pemberitahun Toko di Tutup', 'Pemberitahuan Toko anda di tutup sementara oleh ' . $user->name, $payload);
                    $tenant->update(['is_open' => $request->is_open]);
                    return response()->json($tenant);
                }
            }
        $tenant->update(['is_open' => $request->is_open]);
        return response()->json($tenant);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tenant  $tenant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return response()->json($tenant);
    }

    public function export()
    {
        $record = Tenant::with('business', 'rest_area', 'ruas', 'order', 'category_tenant')->get();
        return Excel::download(new TenantExport(), 'Tenant ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }
}
