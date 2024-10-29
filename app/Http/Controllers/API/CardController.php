<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\BindRequest;
use App\Http\Requests\BindValidateRequest;
use App\Services\Master\BindServices;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function __construct(protected BindServices $service)
    {
    }

    public function index(Request $request)
    {
        $data = $this->service->list($request->customer_id);
        return response()->json($data);
    }

    public function bind(BindRequest $request)
    {
        $result = $this->service->binding($request->all());
        return response()->json($result);
    }

    public function rebind($id)
    {
        $result = $this->service->rebinding($id);
        return response()->json($result);
    }

    public function bindValidate(BindValidateRequest $request, $id)
    {
        $result = $this->service->bindValidate($id, $request->validated());
        return response()->json($result);
    }

    public function unBind($id)
    {
        $result = $this->service->unBinding($id);
        return response()->json($result);
    }
}
