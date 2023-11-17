<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class TransStockKartuResource extends JsonResource
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
            'type' => $this->is_composit ? 'product-composite': $this->type,
            'photo' => $this->photo ? asset($this->photo) : null,
            'name' => $this->name,
            'price_capital' => $this->price_capital,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'satuan_id' => $this->satuan?->id,
            'satuan_type' => $this->satuan?->type,
            'satuan_name' => $this->satuan?->name,
            'stock' => $this->stock,
            'stock_min' => $this->stock_min,
            'last_update' => (string) $this->last_stock->last()?->created_at ?? '',
            'last_action' => $this->last_stock->last()?->stockTypeLabel() ?? '',
            'category_name' => $this->category->name ?? '',
            'is_active' => $this->is_active,
            'is_composit' => $this->is_composit,

            'description' => $this->description,
        ];
    }
}
