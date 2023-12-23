<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $tipeOwner = $this->type == Subscription::OWNER ? true : false;
        $owner = '';

        if ($tipeOwner) {
            $owner = $this->superMerchant?->owner;
        } else {
            $owner = $this->superMerchant?->pic;
        }


        $data =  [
            'id' => $this->id,
            'id_activation' => $this->id_activation,
            'type' => $this->type,
            'super_merchant_id' => $this->super_merchant_id,
            'owner' => $owner,
            'masa_aktif' => $this->masa_aktif,
            'limit_cashier' => $this->limit_cashier,
            'start_date' => (string) $this->created_at,
            'end_date' => (string) $this->end_date,
            'remaining' => $this->remaining,
            'document_type' => $this->document_type,
            'status_aktivasi' => $this->status_aktivasi,
            'detail_aktivasi' => $this->detail_aktivasi,
        ];
        return $data;
    }
}
