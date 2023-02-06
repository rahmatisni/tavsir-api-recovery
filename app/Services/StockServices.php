<?php

namespace App\Services;

use App\Models\Product;
use App\Models\TransOrder;
use App\Models\TransOrderDetil;
use App\Models\User;

class StockServices
{
    public function updateStockProduct(TransOrderDetil $order_detil)
    {
        if ($order_detil->product) {

            $qty = $order_detil->qty;
            $stock = $order_detil->product->stock;
            $update_stock = max(($stock - $qty), 0);

            $order_detil->product->update(['stock' => $update_stock]);
            if ($update_stock < 10) {
               
                $fcm_token = User::where('tenant_id', $order_detil->product->tenant_id)->get();
                $product_name = $order_detil->product?->name;
                $product_id = $order_detil->product?->id;

                $payload = array(
                    'id' => $product_id,
                    'goto' => 'tavsir_product',
                    'action' => 'click'
                );
                foreach ($fcm_token as $value) {
                    if ($value->fcm_token) {
                        $result = sendNotif($value->fcm_token, 'Info', 'Stock product ' . $product_name . ' ' . $update_stock, $payload);
                    }
                }
            }
            return true;
        }
        return false;
    }
}
