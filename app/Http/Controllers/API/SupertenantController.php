<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupertenantRequest;
use App\Models\Supertenant;

class SupertenantController extends Controller
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
        $data = Supertenant::when($name = request()->name, function ($q) use ($name) {
            return $q->where('name', 'like', "%$name%");
        })->get();

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\SupertenantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SupertenantRequest $request)
    {
        $data = new Supertenant();
        $data->fill($request->all());
        $data->save();
        return response()->json($data);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Supertenant  $data
     * @return \Illuminate\Http\Response
     */
    public function show(Supertenant $data)
    {
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\SupertenantRequest  $request
     * @param  \App\Models\Supertenant  $data
     * @return \Illuminate\Http\Response
     */
    public function update(SupertenantRequest $request, Supertenant $data)
    {
        $data->fill($request->all());
        $data->save();
        return response()->json($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Supertenant  $data
     * @return \Illuminate\Http\Response
     */
    public function destroy(Supertenant $data)
    {
        $data->delete();
        return response()->noContent();
    }

    public function getMember(Supertenant $data)
    {
        return response()->json($data->tenant);
    }
}
