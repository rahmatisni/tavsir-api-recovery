<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Http\Requests\BusinessRequest;

class BusinessController extends Controller
{
    public function __construct()
    {
        // $this->middleware('is_admin')->except('index', 'show');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $business = Business::when($name = request()->name, function($q)use ($name){
            return $q->where('name', 'like', "%$name%");
        })->get();

        return response()->json($business);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\BusinessRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BusinessRequest $request)
    {
        $business = new Business();
        $business->fill($request->all());
        $business->save();
        return response()->json($business);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Business  $business
     * @return \Illuminate\Http\Response
     */
    public function show(Business $business)
    {
        return response()->json($business);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\BusinessRequest  $request
     * @param  \App\Models\Business  $business
     * @return \Illuminate\Http\Response
     */
    public function update(BusinessRequest $request, Business $business)
    {
        $business->fill($request->all());
        $business->save();
        return response()->json($business);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Business  $business
     * @return \Illuminate\Http\Response
     */
    public function destroy(Business $business)
    {
        $business->delete();
        return response()->noContent();
    }
}
