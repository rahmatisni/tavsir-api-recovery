<?php

namespace App\Http\Resources\TravShop;

use Illuminate\Http\Resources\Json\JsonResource;

class TsOrderDetilResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_photo' => $this->product ? ($this->product->photo ?  asset($this->product->photo) : null) : null,
            'product_base_price' => $this->base_price,
            'product_price' => $this->price,
            'product_qty' => $this->qty,
            'product_total_price' => $this->total_price,
            'product_note' => $this->note,
            'customize' => $this->customize,
        ];
    }
}
