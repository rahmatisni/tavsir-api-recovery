<?php

namespace App\Http\Controllers\API;

use App\Exports\TemplateStockExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportStockRequest;
use App\Http\Requests\TransStockRequest;
use App\Http\Resources\KartuStockDetilResource;
use App\Http\Resources\KartuStockResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockKeluarResource;
use App\Http\Resources\StockMasukResource;
use App\Http\Resources\TransStockResource;
use App\Imports\StockImport;
use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransStock;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockController extends Controller
{
    public function indexKartu()
    {
        $data = Product::byTenant()->when($name = request()->name, function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->when($sku = request()->sku, function ($q) use ($sku) {
            $q->where('sku', 'like', '%' . $sku . '%');
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            return $q->where('category_id', $category_id);
        });
        if (request()->is_active == '0') {
            $data->where('is_active', '0');
        } else if (request()->is_active == '1') {
            $data->where('is_active', '1');
        }
        $data->latest();
        return response()->json(KartuStockResource::collection($data->get()));
    }

    public function indexMasuk()
    {
        $data = TransStock::with('product')->byTenant()->masuk()->when($name = request()->name, function ($q) use ($name) {
            $q->whereHas('product', function ($qq) use ($name) {
                $qq->where('name', 'like', '%' . $name . '%');
            });
        })->when($status = request()->status, function ($q) use ($status) {
            $q->whereHas('product', function ($qq) use ($status) {
                $qq->where('status', $status);
            });
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            $q->whereHas('product', function ($qq) use ($category_id) {
                $qq->where('category_id', $category_id);
            });
        });
        if (request()->is_active == '0') {
            $data->whereHas('product', function ($qq) {
                $qq->where('is_active', '0');
            });
        } else if (request()->is_active == '1') {
            $data->whereHas('product', function ($qq) {
                $qq->where('is_active', '1');
            });
        }
        $data->latest();
        return response()->json(StockMasukResource::collection($data->get()));
    }

    public function indexKeluar()
    {
        $data = TransStock::with('product')->byTenant()->keluar()->when($name = request()->name, function ($q) use ($name) {
            $q->whereHas('product', function ($qq) use ($name) {
                $qq->where('name', 'like', '%' . $name . '%');
            });
        })->when($category_id = request()->category_id, function ($q) use ($category_id) {
            $q->whereHas('product', function ($qq) use ($category_id) {
                $qq->where('category_id', $category_id);
            });
        });
        if (request()->is_active == '0') {
            $data->whereHas('product', function ($qq) {
                $qq->where('is_active', '0');
            });
        } else if (request()->is_active == '1') {
            $data->whereHas('product', function ($qq) {
                $qq->where('is_active', '1');
            });
        }
        $data->latest();
        return response()->json(StockKeluarResource::collection($data->get()));
    }

    public function kartuShow($id)
    {
        $data = Product::byTenant()->byType(ProductType::PRODUCT)->findOrfail($id);
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

    public function changeStatus($id)
    {
        $data = Product::byType(ProductType::PRODUCT)->findOrfail($id);
        $data->is_active = $data->is_active == 1 ? 0 : 1;
        $data->save();
        return response()->json(['message' => 'Change status success', 'is_active' => $data->is_active]);
    }

    public function downloadTemplateImport()
    {
        return Excel::download(new TemplateStockExport(), 'template import stock.xlsx');
    }

    public function importStock(ImportStockRequest $request)
    {
        $stock = new StockImport($request->type);
        Excel::import($stock, $request->file('file'));
        return response()->json($stock->gethasil(), $stock->gethasil()['status'] ? 200 : 400);
    }
}
