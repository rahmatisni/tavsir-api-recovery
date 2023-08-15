<?php

namespace App\Http\Controllers\API\V2\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductTunggalRequest;
use App\Http\Requests\ProductTunggalUpdateRequest;
use App\Http\Resources\Pos\ProductV2Resource;
use App\Http\Resources\Pos\ProductV2ShowResource;
use App\Http\Resources\ProductRawResource;
use App\Http\Resources\ProductResource;
use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\User;
use App\Services\Pos\ProductServices;
use App\Services\Pos\ProductTunggalServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductTunggalController extends Controller
{
    public function __construct(protected ProductTunggalServices $service)
    {
        $this->middleware('role:'.User::TENANT.','.User::CASHIER);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->responsePaginate(ProductV2Resource::class, $this->service->list($request->search, $request->filter));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductTunggalRequest $request)
    {
        return $this->response($this->service->create($request->validated()));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->response(new ProductV2ShowResource($this->service->show($id)));
    }
   

    public function update($id, ProductTunggalUpdateRequest $request)
    {
        return $this->response($this->service->update($id, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->response($this->service->delete($id));
    }

    public function changeStatus($id)
    {
        return $this->response($this->service->changeStatus($id));
    }
}
