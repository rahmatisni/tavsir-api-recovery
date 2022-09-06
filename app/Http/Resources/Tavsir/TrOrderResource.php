<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TrOrderResource extends JsonResource
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
            'order_id' => $this->order_id,
            'tenant_id' => $this->tenant_id,
            'business_id' => $this->tenant_id,
            'merchant_id' => $this->merchant_id,
            'sub_merchant_id' => $this->sub_merchant_id,
            "payment_method_id" => $this->payment_method_id,
            "order_type" => @$this->order_type,
            "created_at" => @$this->created_at,
            "payment_name" => $this->payment_method && $this->payment_method->name!=null ? $this->payment_method->name : '',
            "payment_id" => $this->payment_id,
            "voucher_id" => $this->voucher_id,
            'casheer_id' => $this->casheer_id,
            'sub_total' => $this->sub_total,
            'discount'=> $this->discount,
            'total' => $this->total,
            'pay_amount' => $this->pay_amount,
            'casheer_name' => $this->casheer->name ?? '',
            'status' => $this->status,
            'payment' => $this->payment,
            'detil' => TrOrderDetilResource::collection($this->detil),
        ];
    }
}
