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
                    $exe = Product::find($value->id);
                    $stock = max($exe->stock - ($value->pivot->qty * $qty), 0);
                    // $value->update([
                    //     'stock' => $stock
                    // ]);
                    $exe->stock = $stock;
                    $exe->save();
                }
            } else {
                $exe = Product::find($product->id);
                $stock = max($exe->stock - ($qty), 0);

              
                // $value->update([
                //     'stock' => $stock
                // ]);
                $exe->stock = $stock;
                $exe->save();
                }
                // $update_stock = max(($stock - $qty), 0);
                // $order_detil->product->update(['stock' => $update_stock]);
                // dd($order_detil->product);

                // $order_detil->save();
            

            if($order_detil->product->stock <= $order_detil->product->stock_min && $order_detil->product->is_notification == 1) {
            // if ($update_stock < 10) {}
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

                            $result = sendNotif($value->fcm_token, '❗Oops.. Stok Produk ' . $product_name . ' Habis!', 'Segera tambahkan stok ya!', $payload);
                        }
                        else {
                            $result = sendNotif($value->fcm_token, '❗Oops.. Stok Produk '.$product_name.' Menipis!', 'Stock product ' . $product_name . ' sisa ' . $update_stock . '. Segera tambahkan stok ya!',$payload);
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }
}
