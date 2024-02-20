<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TsPaymentresource extends JsonResource
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
            'payment_methode_name' => $this->trans_order?->payment_method?->name,
            'payment_methode_logo_url' => $this->trans_order?->payment_method?->logo_url,
            'data' => $this->data
        ];
    }
}
