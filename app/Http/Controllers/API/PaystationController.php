<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaystationRequest;
use App\Http\Resources\PaystationResource;
use App\Models\Paystation;

class PaystationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paystations = Paystation::all();
        return response()->json(PaystationResource::collection($paystations));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePaystationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PaystationRequest $request)
    {
        $paystation = Paystation::create($request->all());
        return response()->json($paystation);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Paystation  $paystation
     * @return \Illuminate\Http\Response
     */
    public function show(Paystation $paystation)
    {
        return response()->json(new PaystationResource($paystation));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePaystationRequest  $request
     * @param  \App\Models\Paystation  $paystation
     * @return \Illuminate\Http\Response
     */
    public function update(PaystationRequest $request, Paystation $paystation)
    {
        $paystation->update($request->all());
        return response()->json($paystation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Paystation  $paystation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Paystation $paystation)
    {
        $paystation->delete();
        return response()->json($paystation);
    }
}
