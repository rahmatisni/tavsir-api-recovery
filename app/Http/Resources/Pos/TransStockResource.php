<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class TransStockResource extends JsonResource
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
            'stock_type' => $this->stock_type,
            'type' => $this->product?->type,
            'photo' => $this->product?->photo ? asset($this->product->photo) : null,
            'name' => $this->product->name ?? '',
            'category_name' => $this->product?->category?->name ?? '',
            'satuan_id' => $this->product?->satuan?->id ?? '',
            'satuan_type' => $this->product?->satuan?->type ?? '',
            'satuan_name' => $this->product?->satuan?->name ?? '',
            'price_capital' => $this->price_capital,
            'total_capital' => $this->total_capital,
            'stock_awal' => $this->recent_stock,
            'stock_masuk' => $this->stock_amount,
            'lates_stock' => $this->lates_stock,
            'keterangan' => $this->keterangan,
            'created_at' => (string) $this->created_at,
            'petugas' => $this->creator->name,
        ];
    }
}
