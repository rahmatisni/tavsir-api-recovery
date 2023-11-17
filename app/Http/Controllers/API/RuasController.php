<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RuasRequest;
use App\Models\Ruas;
use App\Models\RestArea;
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
    public function store(RuasRequest $request)
    {
        $data = new Ruas();

        $data->fill($request->all());
        $data->save();
        return response()->json($data);
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
    public function update(RuasRequest $request, $id)
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
        $rest_area = RestArea::where('ruas_id',$id)->count();
        if($rest_area > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terdapat Area yang masih aktif untuk wilayah '.$ruas->name
            ], 422);
        }
        $ruas->delete();
        return response()->json($ruas);
    }
}
