<?php

namespace App\Services\Pos;

use App\Exports\TemplateStockExport;
use App\Imports\StockImport;
use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\RawProduct;
use App\Models\TransStock;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockServices
{
    public function kartuStock($search = null, $filter = [])
    {
        return Product::with('category','customize','tenant')
                ->byTenant()
                ->myWhereLike(['name','sku'], $search)
                ->myWheres($filter)
                ->orderByDesc('id')
                ->paginate();
    }

    public function showKartu($id)
    {
        return Product::byTenant()->findOrFail($id);
    }
    
    public function stockMasuk($search = null, $filter = [])
    {
        return TransStock::with('product')
            ->byTenant()
            ->masuk()
            ->when($name = $filter['name'] ?? '', function ($q) use ($name) {
                $q->whereHas('product', function ($qq) use ($name) {
                    $qq->where('name', 'like', '%' . $name . '%');
                });
            })
            ->when($sku = $filter['sku'] ?? '', function ($q) use ($sku) {
                $q->whereHas('product', function ($qq) use ($sku) {
                    $qq->where('sku', 'like', '%' . $sku . '%');
                });
            })
            ->when($status = $filter['status'] ?? '', function ($q) use ($status) {
                $q->whereHas('product', function ($qq) use ($status) {
                    $qq->where('status', $status);
                });
            })->when($category_id = $filter['product_id'] ?? '', function ($q) use ($category_id) {
                $q->whereHas('product', function ($qq) use ($category_id) {
                    $qq->where('category_id', $category_id);
                });
            })->when(($filter['status'] ?? '') == '0', function ($q) {
                $q->whereHas('product', function ($qq) {
                    $qq->where('is_active', 0);
                });
            })->when(($filter['status'] ?? '') == '1', function ($q) {
                $q->whereHas('product', function ($qq) {
                    $qq->where('is_active', 1);
                });
            })
            ->orderByDesc('id')
            ->paginate();
    }


    public function stockKeluar($search = null, $filter = [])
    {
        return TransStock::with('product')
                ->byTenant()
                ->keluar()
                ->when($name = $filter['name'] ?? '', function ($q) use ($name) {
                    $q->whereHas('product', function ($qq) use ($name) {
                        $qq->where('name', 'like', '%' . $name . '%');
                    });
                })
                ->when($sku = $filter['sku'] ?? '', function ($q) use ($sku) {
                    $q->whereHas('product', function ($qq) use ($sku) {
                        $qq->where('sku', 'like', '%' . $sku . '%');
                    });
                })
                ->when($category_id = $filter['category_id'] ?? '', function ($q) use ($category_id) {
                    $q->whereHas('product', function ($qq) use ($category_id) {
                        $qq->where('category_id', $category_id);
                    });
                })->when(($filter['is_active'] ?? '') == '0', function ($q) {
                    $q->whereHas('product', function ($qq) {
                        $qq->where('is_active', 0);
                    });
                })->when(($filter['is_active'] ?? '') == '1', function ($q) {
                    $q->whereHas('product', function ($qq) {
                        $qq->where('is_active', 1);
                    });
                })
                ->orderByDesc('id')
                ->paginate();
    }

    public function showMasukKeluar($id)
    {
        return TransStock::byTenant()->findOrfail($id);
    }

    public function storeMasuk($payload)
    {
        try {
            DB::beginTransaction();
            $data = new TransStock();
            $data->product_id = $payload['product_id'];
            $data->stock_type = $data::IN;
            $data->recent_stock = $data->product->stock;
            $data->stock_amount = $payload['stock'];
            $data->keterangan = $payload['keterangan'];
            $data->price_capital = $payload['price_capital'];
            $data->total_capital = $payload['price_capital'] * $payload['stock'];
            $data->save();
            $data->product()->update([
                'stock' => $data->lates_stock,
                'price_capital' => $data->price_capital,
                'price_min' => $data->product->price_min < $data->price_capital ? $data->product->price_min : $data->price_capital,
                'price_max' => $data->product->price_max > $data->price_capital ? $data->product->price_max : $data->price_capital,
            ]);
            DB::commit();
            return $data;
        } catch (\Throwable $th) {
            DB::rollBack();
            abort(['message' => $th->getMessage()], 422);
        }
    }

    function storeKeluar($payload)
    {
        try {
            DB::beginTransaction();
            $data = new TransStock();
            $data->product_id = $payload['product_id'];
            $data->stock_type = $data::OUT;
            $data->recent_stock = $data->product->stock;
            $data->stock_amount = $payload['stock'];
            $data->keterangan = $payload['keterangan'];
            $data->price_capital = $payload['price_capital'];
            $data->total_capital = $payload['price_capital'] * $payload['stock'];
            $data->save();
            $data->product()->update(['stock' => $data->lates_stock]);
            DB::commit();
            return $data;
            return response()->json($data);
        } catch (\Throwable $th) {
            DB::rollBack();
            abort(['message' => $th->getMessage()], 422);
        }
    }

    public function changeStatus($id)
    {
        $data = Product::findOrFail($id);
        $data->is_active = $data->is_active == 1 ? 0 : 1;
        $data->save();
        return ['message' => 'Change status success', 'is_active' => $data->is_active];
    }

    public function downloadTemplateImport()
    {
        return Excel::download(new TemplateStockExport(), 'template import stock.xlsx');
    }

    public function importStock($payload)
    {
        $stock = new StockImport($payload['type']);
        Excel::import($stock, $payload['file']);
        return $stock->gethasil();
    }

    public function listProduk(){
        return Product::with('category','customize','tenant','satuan')->byTenant()->byType(ProductType::PRODUCT)->where('is_composit', 0)->orderby('name','asc')->get();
    }
    public function listProdukRAW(){
        return Product::with('category','customize','tenant','satuan')->byTenant()->byType(ProductType::BAHAN_BAKU)->orderby('name','asc')->get();
    }
}
