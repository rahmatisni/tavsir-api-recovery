<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KartuStockResource extends JsonResource
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
            'photo' => $this->photo ? asset($this->photo) : null,
            'name' => $this->name,
            'stock' => $this->stock,
            'last_update' => (string) $this->last_stock->last()?->created_at ?? '',
            'last_action' => $this->last_stock->last()?->stockTypeLabel() ?? '',
            'category_name' => $this->category->name ?? '',
            'is_active' => $this->is_active,
            'is_composit' => $this->is_composit,
            'type' => $this->product_type
        ];
    }
}
