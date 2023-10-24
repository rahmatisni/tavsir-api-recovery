<?php

namespace App\Http\Controllers\API;

use App\Models\RestArea;
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
        $data = RestArea::withTrashed()->when($name = request()->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        });
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
        $restArea->delete();
        return response()->noContent();
    }

    public function updateStatus()
    {
        $rest_area = RestArea::whereIn('id', request()->rest_area_id);
        $rest_area->update(['is_open' => request()->is_open]);

        return response()->json($rest_area->get());
    }
}
