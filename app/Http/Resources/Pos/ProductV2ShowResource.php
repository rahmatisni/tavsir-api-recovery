<?php

namespace App\Http\Resources\Pos;

use App\Http\Resources\CustomizeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductV2ShowResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? '',
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? '',
            'photo' => $this->photo ? asset($this->photo) : null,
            'price' => $this->price,
            'price_capital' => $this->price_capital,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'is_notification' => $this->is_notification,
            'price_capital' => $this->price_capital,
            'stock' => $stock,
            'stock_min' => $this->stock_min,
            'is_active' => $this->is_active,
            'satuan_id' => $this->satuan_id,
            'satuan_name' => $this->satuan?->name,
            'satuan_type' => $this->satuan?->type,
            'description' => $this->description,
            'customize' => CustomizeResource::collection($this->customize),
            'trans_product_raw' => RawDetilResource::collection($this->trans_product_raw()->withTrashed()->get()),
        ];
    }
}
