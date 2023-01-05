<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransStockRequest;
use App\Http\Resources\KartuStockDetilResource;
use App\Http\Resources\KartuStockResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockKeluarResource;
use App\Http\Resources\StockMasukResource;
use App\Http\Resources\TransStockResource;
use App\Models\Product;
use App\Models\TransStock;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function indexKartu()
    {
        $data = Product::when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($status = request()->status, function ($q) use ($status) {
            $q->where('status', $status);
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })
            ->get();
        return response()->json(KartuStockResource::collection($data));
    }

    public function indexMasuk()
    {
        $data = TransStock::with('product')->masuk()->when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($sku = request()->sku, function ($q) use ($sku) {
            $q->where('sku', 'like', '%' . $sku . '%');
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->get();
        return response()->json(StockMasukResource::collection($data));
    }

    public function indexKeluar()
    {
        $data = TransStock::with('product')->keluar()->when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($sku = request()->sku, function ($q) use ($sku) {
            $q->where('sku', 'like', '%' . $sku . '%');
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        })->get();
        return response()->json(StockKeluarResource::collection($data));
    }

    public function kartuShow($id)
    {
        $data = Product::findOrfail($id);
        return response()->json(new KartuStockDetilResource($data));
    }

    public function storeMasuk(TransStockRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new TransStock();
            $data->product_id = $request->product_id;
            $data->stock_type = $data::IN;
            $data->recent_stock = $data->product->stock;
            $data->stock_amount = $request->stock;
            $data->keterangan = $request->keterangan;
            $data->save();
            $data->product()->update(['stock' => $data->lates_stock]);
            DB::commit();
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function storeKeluar(TransStockRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = new TransStock();
            $data->product_id = $request->product_id;
            $data->stock_type = $data::OUT;
            $data->recent_stock = $data->product->stock;
            $data->stock_amount = $request->stock;
            $data->keterangan = $request->keterangan;
            $data->save();
            $data->product()->update(['stock' => $data->lates_stock]);
            DB::commit();
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\TransStockRequest  $request
     * @param  \App\Models\TransStock  $product
     * @return \Illuminate\Http\Response
     */
    public function update(TransStockRequest $request, TransStock $product)
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

    public function changeStatus($id)
    {
        $data = Product::findOrfail($id);
        $data->is_active = $data->is_active == 1 ? 0 : 1;
        $data->save();
        return response()->json(['message' => 'Change status success', 'is_active' => $data->is_active]);
    }
}
