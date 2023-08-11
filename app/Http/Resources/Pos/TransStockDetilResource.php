<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class TransStockDetilResource extends JsonResource
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
            'satuan_id' => $this->satuan?->id,
            'satuan_type' => $this->satuan?->type,
            'satuan_name' => $this->satuan?->name,
            'last_update' => (string) $this->last_stock->first()?->created_at ?? '',
            'last_action' => $this->last_stock->first()?->stockTypeLabel() ?? '',
            'category_name' => $this->category->name ?? '',
            'is_active' => $this->is_active,
        ];
    }
}
