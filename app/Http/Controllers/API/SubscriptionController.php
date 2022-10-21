<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Http\Requests\BusinessRequest;
use App\Http\Requests\SubscriptionChangeStatusRequest;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;

class SubscriptionController extends Controller
{
    public function index()
    {
        $data = Subscription::when($business_id = request()->business_id, function($q)use ($business_id){
            return $q->where('business_id',$business_id);
        })->get();

        return response()->json(SubscriptionResource::collection($data));
    }

    public function store(SubscriptionRequest $request)
    {
        $data = new Subscription();
        $data->fill($request->all());
        $data->save();
        return response()->json($data);
    }

    public function show($id)
    {
        $data = Subscription::findOrfail($id);
        return response()->json(new SubscriptionResource($data));
    }

    public function update(SubscriptionRequest $request, $id)
    {
        $data = Subscription::findOrfail($id);
        $data->fill($request->all());
        $data->save();
        return response()->json($data);
    }

    public function destroy($id)
    {
        $data = Subscription::findOrfail($id);
        $data->delete();
        return response()->noContent();
    }

    public function changeStatus(SubscriptionChangeStatusRequest $request, $id)
    {
        $data = Subscription::findOrfail($id);
        $data->status = $request->status;
        $data->save();
        return response()->json($data);
    }
}
