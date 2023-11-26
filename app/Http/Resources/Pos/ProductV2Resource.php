<?php

namespace App\Http\Resources\Pos;

use App\Http\Resources\CustomizeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductV2Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $cek_product_have_not_active = $this->trans_product_raw->where('is_active',0)->count();
        $stock = $this->stock;
        if($this->is_composit == 1){
            if($cek_product_have_not_active > 0){
                $stock = 0;
            }
        }
        return [
            'id' => $this->id,
            'type' => $this->type,
            'is_composit' => $this->is_composit,
            'sku' => $this->sku,
            'name' => $this->name,
            'tenant_name' => $this->tenant?->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? '',
            'photo' => $this->photo ? asset($this->photo) : null,
            'discount' => $this->discount,
            'price' => $this->price,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'price_capital' => $this->price_capital,
            'stock' => $stock,
            'stock_min' => $this->stock_min,
            'satuan_id' => $this->satuan_id ?? 7,
            'satuan_name' => $this->satuan?->name ?? 'unit',
            'satuan_type' => $this->satuan?->type ?? 'pcs',
            'is_active' => $this->is_active,
            'is_notification' => $this->is_notification,
        ];
    }
}
