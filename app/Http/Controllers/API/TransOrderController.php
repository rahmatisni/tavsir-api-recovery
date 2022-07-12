<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransOrderRequest;
use App\Http\Resources\TransOrderResource;
use App\Models\TransOrder;

class TransOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $transOrders = TransOrder::all();
        return response()->json(TransOrderResource::collection($transOrders));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTransOrderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TransOrderRequest $request)
    {
        $transOrder = TransOrder::create($request->all());
        return response()->json(new TransOrderResource($transOrder));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\TransOrder  $transOrder
     * @return \Illuminate\Http\Response
     */
    public function show(TransOrder $transOrder)
    {
        return response()->json(new TransOrderResource($transOrder));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTransOrderRequest  $request
     * @param  \App\Models\TransOrder  $transOrder
     * @return \Illuminate\Http\Response
     */
    public function update(TransOrderRequest $request, TransOrder $transOrder)
    {
        $transOrder->update($request->all());
        return response()->json(new TransOrderResource($transOrder));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TransOrder  $transOrder
     * @return \Illuminate\Http\Response
     */
    public function destroy(TransOrder $transOrder)
    {
        $transOrder->delete();
        return response()->json(new TransOrderResource($transOrder));
    }
}
