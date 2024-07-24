<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\NumberSaveRequest;
use App\Http\Resources\TravShop\NumberSaveResource;
use App\Models\NumberSave;
use App\Services\Travshop\NumberSaveServices;
use Illuminate\Http\Request;

class NumberSaveController extends Controller
{
    public function __construct(protected NumberSaveServices $service)
    {        
    }

    public function index(Request $request)
    {
        // $data = $this->service->list($request->filter);
        $data = $this->service->list($request->customer_id);

        return $this->response(NumberSaveResource::collection($data));
    }

    public function show($id)
    {
        $data = $this->service->show($id);
        return $this->response(new NumberSaveResource($data));
    }

    public function update(NumberSaveRequest $request, $id)
    {
        $data = $this->service->update($id, $request->validated());
        return response()->json($data);
    }

    public function destroy($id)
    {
        $data = $this->service->delete($id);
        return response()->json($data);
    }
}
