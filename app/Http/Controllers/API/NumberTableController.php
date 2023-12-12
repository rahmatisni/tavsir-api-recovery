<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\NumberTableRequest;
use App\Models\NumberTable;
use App\Models\User;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class NumberTableController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.User::TENANT.','.User::CASHIER);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(NumberTable::byTenant()->myWhereLike(['name'], request()->search)->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreNumberTableRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(NumberTableRequest $request)
    {
        $data = new NumberTable();
        $data->fill($request->validated());
        $data->tenant_id = auth()->user()->tenant_id;
        $data->save();
        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\NumberTable  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data =  NumberTable::byTenant()->findOrFail($id);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\NumberTableRequest  $request
     * @param  \App\Models\NumberTable  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function update($id, NumberTableRequest $request)
    {
        $data =  NumberTable::byTenant()->findOrFail($id);
        $data->fill($request->validated());
        $data->save();
        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\NumberTable  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data =  NumberTable::byTenant()->findOrFail($id);
        $data->delete();
        return response()->json(['message' => 'Success delete']);
    }


     /**
     * Show QR
     *
     * @param  \App\Models\NumberTable  $extraPrice
     * @return svg
     */
    public function showQr($id)
    {
        $data =  NumberTable::byTenant()->findOrFail($id);
        $self_order_url = env('URL_SELF_ORDER','https://getpay-selforder.jmto.co.id');
        $url = $self_order_url.'?'.http_build_query(['tenant_id' => auth()->user()->tenant_id, 'nomor' => $data->name]);
        return QrCode::size(200)
        // ->backgroundColor(254, 200, 26)
        // ->color(1, 55, 182)
        ->margin(1)
        ->generate($url);
    }
}
