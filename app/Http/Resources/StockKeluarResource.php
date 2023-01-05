<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockKeluarResource extends JsonResource
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
            'photo' => $this->product?->photo ? asset($this->product->photo) : null,
            'name' => $this->product->name ?? '',
            'category_name' => $this->product?->category?->name ?? '',
            'stock_awal' => $this->recent_stock,
            'stock_masuk' => $this->stock_amount,
            'lates_stock' => $this->lates_stock,
            'keterangan' => $this->keterangan,
            'created_at' => (string) $this->created_at,
        ];
    }
}
