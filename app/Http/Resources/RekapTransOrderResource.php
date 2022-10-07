<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RekapTransOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            'created_at' => $this->created_at->format('d-m-Y H:i:s'),
            'order_id' => $this->order_id,
            'total_product' => $this->detil->count(),
            'total' => $this->total,
            'payment_method' => $this->payment_method->name,
            'order_type' => $this->order_type,
            'order_type_label' => $this->labelOrderType(),
        ];
    }
}
