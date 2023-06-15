<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SubscriptionCalculationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    protected $price;
    public function price($value){
        $this->price = $value;
        return $this;
    }

    public function toArray($request)
    {
        $data =  [
            'id' => $this->id,
            'id_activation' => $this->id_activation,
            'start_date' => (string) $this->created_at,
            'limit_tenant' => $this->limit_tenant,
            'price_tenant' =>  $this->price->price_tenant,
            'total_price_tenant' => $this->limit_tenant * $this->price->price_tenant,

            'limit_cashier' => $this->limit_cashier,
            'price_cashear' => $this->price->price_cashier,
            'total_price_cashear' => $this->limit_cashier * $this->price->price_cashier,
            'masa_aktif' => $this->masa_aktif,
            'end_date' => (string) $this->end_date,
            'detail_aktivasi' => $this->detail_aktivasi,
        ];
        return $data;
    }
}
