<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Http\Requests\BukaTutupTokoRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Models\TransSaldo;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(TenantResource::collection(Tenant::all()));
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
        $data->fill($request->all());
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
    public function bukaTutupToko(BukaTutupTokoRequest $request)
    {
        $tenant = Tenant::find($request->id);
        $tenant->update(['is_open'=>$request->is_open]);
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
}
