<?php

namespace App\Http\Controllers\API;

use App\Models\RestArea;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\RestAreaRequest;
use App\Http\Resources\RestAreaResource;

class RestAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // dd('x');

        $role = auth()->user()->role;
        $data = RestArea::when($name = request()->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        });
        // ->when($role === 'OWNER', function ($q) {
        //     $data_tenant = Tenant::where('business_id', auth()->user()->business_id)->distinct()->pluck('rest_area_id')->toArray();
        //     return $q->whereIn('id', $data_tenant);
        // });
        $data = $data->get();
        if (request()->lat && request()->lon) {
            $data = $data->filter(function ($item) {
                return $this->haversine($item->latitude, $item->longitude, request()->lat, request()->lon, request()->distance ?? 1);
            });
        }
        return response()->json(RestAreaResource::collection($data));
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RestAreaRequest $request)
    {
        $data = new RestArea();
        $data->fill($request->all());
        $data->save();
        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestArea  $restArea
     * @return \Illuminate\Http\Response
     */
    public function show(RestArea $restArea)
    {
        return response()->json(new RestAreaResource($restArea));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RestArea  $restArea
     * @return \Illuminate\Http\Response
     */
    public function update(RestAreaRequest $request, RestArea $restArea)
    {
        $restArea->fill($request->all());
        $restArea->save();
        return response()->json($restArea);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestArea  $restArea
     * @return \Illuminate\Http\Response
     */
    public function destroy(RestArea $restArea)
    {
        $tenant = Tenant::where('rest_area_id',$restArea->id)->count();
        if($tenant > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'area '.$restArea->name. ' masih memiliki tenant aktif!'
            ], 422);
        }
        $restArea->delete();
        // return response()->noContent();
        return response()->json([
            'status' => 'Sukses',
            'message' => 'Rest area '.$restArea->name. ' berhasil dihapus!'
        ], 200);
    }

    public function updateStatus()
    {
        $rest_area = RestArea::whereIn('id', request()->rest_area_id);
        $rest_area->update(['is_open' => request()->is_open]);

        return response()->json($rest_area->get());
    }
}
