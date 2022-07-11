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
            'category' => $this->category,
            'name' => $this->name,
            'variant_id' => $this->variant_id,
            'variant_name' => $this->variant_name,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'description' => $this->description,
        ];
    }
}
