<?php

namespace App\Http\Controllers\API\V2\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\RawProductAddStockRequest;
use App\Http\Requests\RawProductRequest;
use App\Http\Requests\RawProductUpdateRequest;
use App\Http\Resources\ProductRawResource;
use App\Models\User;
use App\Models\Product;
use App\Services\Pos\ProductBahanBakuServices;
use Illuminate\Http\Request;

class ProductBahanBakuController extends Controller
{
    public function __construct(protected ProductBahanBakuServices $service)
    {
        $this->middleware('role:' . User::TENANT . ',' . User::CASHIER);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->responsePaginate(ProductRawResource::class, $this->service->list($request->search, $request->filter));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\RawProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RawProductRequest $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $product = Product::where('sku', $request->sku)->where('tenant_id', $tenant_id)
            ->count();
        if ($product > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'SKU sudah digunakan pada product ' . $product->name
            ], 422);
        }
        return $this->response($this->service->create($request->validated()));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->response(new ProductRawResource($this->service->show($id)));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function changeStatus($id)
    {
        return $this->response($this->service->changeStatus($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\RawProductUpdateRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update($id, RawProductUpdateRequest $request)
    {
        $tenant_id = auth()->user()->tenant_id;
        $product = Product::where('sku', $request->sku)->where('tenant_id', $tenant_id)->where('id','!=',$request->id)
        ->get();
        if ($product->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'SKU sudah digunakan pada product ' . $product[0]->name
            ], 422);
        }
        return $this->response($this->service->update($id, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->response($this->service->delete($id));
    }
}
