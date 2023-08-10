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
            'stock' => $this->stock,
            'stock_min' => $this->stock_min,
            'is_active' => $this->is_active,
            'is_notification' => $this->is_notification,
            'description' => $this->description,
            'customize' => CustomizeResource::collection($this->customize),
            'trans_product_raw' => RawDetilResource::collection($this->trans_product_raw),
        ];
    }
}
