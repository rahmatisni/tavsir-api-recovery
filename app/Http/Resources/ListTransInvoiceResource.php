<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListTransInvoiceResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'cashier_name' => $this->cashier->name ?? '',
            'pay_station_name' => $this->pay_station->name ?? '',
            'claim_date' => $this->claim_date,
            'paid_date' => $this->paid_date,
            'nominal' => $this->nominal,
            'status' => $this->status,
        ];
    }
}
