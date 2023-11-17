<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class RawDetilResource extends JsonResource
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
            'name' => $this->name,
            'is_active' => $this->is_active,
            'satuan_id' => $this->satuan?->id,
            'satuan_name' => $this->satuan?->name,
            'stock' => $this->stock,
            'price_min' => $this->price_min,
            'price_max' => $this->price_max,
            'qty' => $this->pivot?->qty,
        ];
    }
}
