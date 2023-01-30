<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetilSupertenantResource extends JsonResource
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
            'product_qty' => $this->qty,
            'product_note' => $this->note,
            'base_price' => $this->base_price,
            'price' => $this->price,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'customize' => $this->customize,
        ];
    }
}
