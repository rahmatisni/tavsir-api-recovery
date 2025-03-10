<?php

namespace App\Http\Resources\Tavsir;

use App\Http\Resources\CustomizeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSupertenantResource extends JsonResource
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
            'sku' => $this->sku,
            'name' => $this->name,
            'tenant_name' => $this->tenant?->name,
            'tenant_is_open' => $this->tenant?->is_open,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? '',
            'photo' => $this->photo ? asset($this->photo) : null,
            'discount' => $this->discount,
            'price' => $this->price,
            'stock' => $this->stock,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'customize' => CustomizeResource::collection($this->customize),
        ];
    }
}
