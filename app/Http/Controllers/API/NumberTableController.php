<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\NumberTableRequest;
use App\Models\NumberTable;
use App\Models\User;

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
        return response()->json(NumberTable::byTenant()->get());
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
     * Change Status
     *
     * @param  \App\Models\NumberTable  $extraPrice
     * @return \Illuminate\Http\Response
     */
    public function changeStatus($id, ChangeStatusRequest $request)
    {
        $data =  NumberTable::byTenant()->findOrFail($id);
        $data->status = $request->status;
        $data->save();
        return response()->json($data);
    }
}
