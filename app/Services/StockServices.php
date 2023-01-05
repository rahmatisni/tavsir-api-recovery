<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;

class StockServices
{
    public function updateStockProduct(TransOrderDetil $order_detil)
    {
        if ($order_detil->product) {

            $qty = $order_detil->qty;
            $stock = $order_detil->product->stock;
            $update_stock = $stock - $qty;

            $order_detil->product->update(['stock' => max($update_stock, 0)]);
            return true;
        }
        return false;
    }
}
