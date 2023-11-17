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
        $product = $order_detil->product;
        if ($product) {
            $qty = $order_detil->qty;
            $stock = $order_detil->product->stock;
            $update_stock = 0;
            if ($product->is_composit == 1) {
                foreach ($product->trans_product_raw as $value) {
                    $stock = max($value->stock - ($value->pivot->qty * $qty), 0);
                    $value->update([
                        'stock' => $stock
                    ]);
                }
            } else {
                $update_stock = max(($stock - $qty), 0);
                $order_detil->product->update(['stock' => $update_stock]);
            }
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
                        if ($update_stock == 0) {

                            $result = sendNotif($value->fcm_token, '❗Oops.. Stok Paket ' . $product_name . ' Habis!', 'Segera tambahkan stok ya!', $payload);
                        }
                        else {
                            $result = sendNotif($value->fcm_token, '❗Oops.. Stok Paket '.$product_name.' Menipis!', 'Stock product ' . $product_name . ' sisa ' . $update_stock . '. Segera tambahkan stok ya!',$payload);
                        }

                        // ⚠ Oops.. Stok Paket Ayam Goreng Menipis!
                        // Stok Paket Ayam Goreng sisa 9. Segera tambahkan stok ya!
                    }
                }
            }
            return true;
        }
        return false;
    }
}
