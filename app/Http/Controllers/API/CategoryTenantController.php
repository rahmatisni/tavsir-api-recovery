<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatgeoryTenantRequest;
use App\Services\CategoryTenantService;
use Illuminate\Http\Request;

class CategoryTenantController extends Controller
{
    public function __construct(
        protected CategoryTenantService $service,
    )
    {}
   
    public function index()
    {
        $data = $this->service->baseAll();
        return response()->json($data);
    }

    public function store(CatgeoryTenantRequest $request)
    {
        $data = $this->service->baseCreate($request->validated());
        return response()->json($data);
    }

    public function show($id)
    {
        $data = $this->service->baseFind($id);
        return response()->json($data);
    }

    public function update($id, Request $request)
    {
        $data = $this->service->baseUpdate($id, $request->all());
        return response()->json($data);
    }

    public function delete($id)
    {
        $data = $this->service->baseDelete([$id]);
        return response()->noContent();
    }
}
