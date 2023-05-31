<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionDashboardResource extends JsonResource
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
            'name' => $this->productKiosbank()?->name,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'sub_total' => $this->sub_total,
            'fee' => $this->fee,
            'service_fee' => $this->service_fee,
            'total' => $this->total,
            'harga_kios' => $this->harga_kios,
            'created_at' =>(string) $this->created_at,
            'updated_at' =>(string) $this->updated_at,
            'log_kios' => $this->log_kiosbank,
            'log_payment' => $this->payment,
            'margin' => $this->getMargin(),
        ];
    }
}
