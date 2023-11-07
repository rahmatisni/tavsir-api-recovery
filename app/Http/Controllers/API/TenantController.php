<?php

namespace App\Http\Controllers\API;

use App\Exports\TenantExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use Illuminate\Http\Request;
use App\Http\Requests\BukaTutupTokoRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\User;
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
    public function index()
    {
        return response()->json(TenantResource::collection(Tenant::with('business','rest_area','ruas','order','category_tenant')->get()));
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

     public function setPayment(Request $request,Tenant $tenant)
     {
        if (auth()->user()->role === 'TENANT') {
            $tenant = Tenant::find(auth()->user()->tenant_id);
            $tenant->update(['list_payment' => $request->list_payment]);
            return response()->json(["status"=>'success','role'=>auth()->user()->role,'message'=>'Setting Payment Berhasil'],200);
        }
            if(in_array(auth()->user()->role,[User::SUPERADMIN, User::ADMIN])){
            if(!$request->tenant_id){
                return response()->json(["status"=>'Failed','role'=>'UNKNOWN','message'=>'No Tenant Requested'],422);
            }
            $tenant = Tenant::find($request->tenant_id);
            $tenant->update(['list_payment_bucket' => $request->list_payment]);
            return response()->json(["status"=>'success','role'=>auth()->user()->role,'message'=>'Setting Payment untuk Tenant '.$tenant->name.' Berhasil'],200);
        }
        return response()->json(["status"=>'Failed','role'=>'UNKNOWN','message'=>'DONT TRY'],422);
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

    public function export(){
        $record = Tenant::with('business','rest_area','ruas','order','category_tenant')->get();
        return Excel::download(new TenantExport(), 'Tenant ' . Carbon::now()->format('d-m-Y') . '.xlsx');
    }
}
