<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RatingRequest;
use App\Http\Resources\TransOrderResource;
use App\Models\TransOrder;

class RatingController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RatingRequest $request, TransOrder $id)
    {
        $id->update($request->all());
        return response()->json(new TransOrderResource($id));
    }
}
