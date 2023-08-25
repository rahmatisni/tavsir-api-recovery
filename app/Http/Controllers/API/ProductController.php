<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Product::with('category','customize','tenant')->byType(ProductType::PRODUCT)->when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($sku = request()->sku, function ($q) use ($sku) {
            $q->where('sku', 'like', '%' . $sku . '%');
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->get();
        return response()->json(ProductResource::collection($data));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'sku' => 'max:5',
            ]);
            if ($validator->fails())
            {
                return response(['message'=>$validator->errors()->all()], 422);
            }          
            DB::beginTransaction();
            $data = new Product();
            $data->fill($request->all());
            $data->type = ProductType::PRODUCT;
            $data->save();
            $data->trans_stock()->create([
                'stock_type' => TransStock::INIT,
                'recent_stock' => 0,
                'stock_amount' => $data->stock,
                'created_by' => auth()->user()->id,
            ]);
            $data->customize()->sync($request->customize);
            DB::commit();
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        return response()->json(new ProductResource($product));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();
            $product->fill($request->all());
            $product->save();
            $product->customize()->sync($request->customize);
            DB::commit();
            return response()->json($product);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        if (request()->ids) {
            $product->whereIn('id', request()->ids)->delete();
            return response()->json($product);
        } else {
            $product->delete();
            return response()->json($product);
        }
    }

    public function updateStatus()
    {
        $product = Product::byType(ProductType::PRODUCT)->whereIn('id', request()->product_id);
        $product->update(['is_active' => request()->is_active]);

        return response()->json($product->get());
    }
}
