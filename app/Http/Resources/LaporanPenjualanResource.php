<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanPenjualanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $product_detil = [];
        $pilihan_price = [];

        foreach ($this->customize as $v) {
            // $product_detil = $v->customize_name . ': ' . $v->pilihan_name;
            // $pilihan_price = $v->pilihan_price;

            array_push($product_detil, $v->customize_name . ': ' . $v->pilihan_name);
            array_push($pilihan_price,  (int)$v->pilihan_price);
        };
        return [
            'sku' => $this->product->sku ?? '',
            'nama_product' => ($this->product->name ?? ''),
            'nama_varian' => ($product_detil ?? ''),
            'kategori' => $this->product->category->name ?? '',
            'jumlah' => $this->qty,
            'harga' => $this->base_price,
            'harga_varian' => $pilihan_price,
            'pendapatan' => $this->total_price,
        ];
    }
}
