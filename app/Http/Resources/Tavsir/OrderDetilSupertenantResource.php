<?php

namespace App\Http\Resources\Tavsir;

use App\Models\TransOrderDetil;
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
        $is_refund = $this->trans_order->is_refund;
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product_name,
            'product_photo' => $this->product ? ($this->product->photo ?  asset($this->product->photo) : null) : null,
            'product_qty' => $this->qty,
            'product_note' => $this->note,
            'base_price' => $is_refund ? $this->basePriceRefund() : $this->base_price ,
            'price' => $is_refund ? $this->priceRefund() : $this->price,
            'total_price' => $is_refund ? $this->totalPriceRefund() : $this->total_price,
            'status' => $this->status,
            'customize' => $this->customize,
        ];
    }
}
