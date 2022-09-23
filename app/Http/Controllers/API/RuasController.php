<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Ruas;
use Illuminate\Http\Request;

class RuasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ruas = Ruas::all();
        return response()->json($ruas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $ruas = Ruas::create($request->all());
        return response()->json($ruas);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ruas  $ruas
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $ruas = Ruas::findOrFail($id);
        return response()->json($ruas);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ruas  $ruas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        $ruas = Ruas::findOrFail($id);
        $ruas->update($request->all());
        return response()->json($ruas);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ruas  $ruas
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ruas = Ruas::findOrFail($id);
        $ruas->delete();
        return response()->json($ruas);
    }
}
