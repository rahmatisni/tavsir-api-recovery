<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\ExtraPriceRequest;
use App\Models\ExtraPrice;
use App\Models\User;

class ExtraPriceController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.User::CASHIER);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(ExtraPrice::byTenant()->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreExtraPriceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ExtraPriceRequest $request)
    {
        $data = new ExtraPrice();
        $data->fill($request->validated());
        $data->tenant_id = auth()->user()->tenant_id;
        $data->save();
        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ExtraPrice  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data =  ExtraPrice::byTenant()->findOrFail($id);
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ExtraPriceRequest  $request
     * @param  \App\Models\ExtraPrice  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function update($id, ExtraPriceRequest $request)
    {
        $data =  ExtraPrice::byTenant()->findOrFail($id);
        $data->fill($request->validated());
        $data->save();
        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExtraPrice  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $data =  ExtraPrice::byTenant()->findOrFail($id);
        $data->delete();
        return response()->json(['message' => 'Success delete']);
    }


     /**
     * Change Status
     *
     * @param  \App\Models\ExtraPrice  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function changeStatus($id, ChangeStatusRequest $request)
    {
        $data =  ExtraPrice::byTenant()->findOrFail($id);
        $data->status = $request->status;
        $data->save();
        return response()->json($data);
    }
}
