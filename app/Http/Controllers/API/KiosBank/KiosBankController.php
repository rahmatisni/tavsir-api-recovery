<?php

namespace App\Http\Controllers\API\KiosBank;

use App\Http\Controllers\Controller;
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
}
