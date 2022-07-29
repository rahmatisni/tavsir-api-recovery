<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomizeRequest;
use App\Models\Customize;
use Illuminate\Http\Request;

class CustomizeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customizes = Customize::ByTenant()
                                ->when($name = request()->name, function($q) use($name){
                                    $q->where('name', 'like', '%'.$name.'%');
                                })->get();
        return response()->json($customizes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CustomizeRequest $request)
    {
        $customize = Customize::create($request->all());
        return response()->json($customize);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customize  $customize
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customize = Customize::ByTenant()->findOrFail($id);
        return response()->json($customize);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customize  $customize
     * @return \Illuminate\Http\Response
     */
    public function update(CustomizeRequest $request, Customize $customize)
    {
        $customize->update($request->all());
        return response()->json($customize);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customize  $customize
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customize $customize)
    {
        $customize->delete();
        return response()->json($customize);
    }
}
