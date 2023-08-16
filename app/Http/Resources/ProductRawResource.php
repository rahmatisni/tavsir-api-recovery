<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductRawResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? '',
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? '',
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'price_capital' => $this->price_capital,
            'photo' => $this->photo ? asset($this->photo) : null,
            'price' => $this->price,
            'stock' => $this->stock,
            'stock_min' => $this->stock_min,
            'satuan_id' => $this->satuan_id,
            'satuan_type' => $this->satuan?->type,
            'satuan_name' => $this->satuan?->name,
            'is_active' => $this->is_active,
            'is_notification' => $this->is_notification,
            'description' => $this->description,
        ];
    }
}
