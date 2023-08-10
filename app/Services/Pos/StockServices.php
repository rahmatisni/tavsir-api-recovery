<?php

namespace App\Services\Pos;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\RawProduct;
use App\Models\TransStock;
use Illuminate\Support\Facades\DB;

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
    
    public function stockMasuk($search = null, $filter = [])
    {
        return TransStock::with('product')
            ->byTenant()
            ->masuk()
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
}
