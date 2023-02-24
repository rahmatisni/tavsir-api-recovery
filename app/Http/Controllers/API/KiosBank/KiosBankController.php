<?php

namespace App\Http\Controllers\API\KiosBank;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrderPulsaRequest;
use App\Services\External\KiosBankService;
use Illuminate\Http\Request;

class KiosBankController extends Controller
{
    public function __construct(
        protected KiosBankService $service,
    )
    {}
   
    public function index()
    {
        $data = $this->service->getProduct();
        return response()->json($data);
    }

    public function show($id)
    {
        $data = $this->service->showProduct($id);
        return response()->json($data);
    }

    public function listOperatorPulsa()
    {
        $data = $this->service->getListOperatorPulsa();
        return response()->json($data);
    }

    public function listProductOperatorPulsa($id)
    {
        $data = $this->service->listProductOperatorPulsa($id);
        return response()->json($data);
    }

    public function orderPulsa(OrderPulsaRequest $reqest)
    {
        $data = $this->service->orderPulsa($reqest->validated());
        return response()->json($data);
    }

    public function callback(Request $reqest)
    {
        $data = $this->service->callback($reqest->all());
        return response()->json($data);
    }
}
