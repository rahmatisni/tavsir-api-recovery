<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name,
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? '',
            'photo' => $this->photo ? asset($this->photo) : null,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'customize' => CustomizeResource::collection($this->customize),
        ];
    }
}
