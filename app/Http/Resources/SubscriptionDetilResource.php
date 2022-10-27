<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionDetilResource extends JsonResource
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
        $limit_cashier = 0;
        $total_tenant = 0;

        if ($tipeOwner) {
            $owner = $this->superMerchant->owner;
            $limit_cashier = $this->limit_cashier ?? 0;
            $total_tenant = $this->superMerchant->tenant()->count() ?? 0;
        } else {
            $owner = $this->superMerchant->pic;
            $limit_cashier = 'Unlimited';
            $total_tenant = 'Unlimited';
        }

        $data =  [
            'id' => $this->id,
            'id_activation' => $this->id_activation,
            'file' => $this->file ? asset($this->file) : null,
            'type' => $this->type,
            'super_merchant_id' => $this->super_merchant_id,
            'owner' => $owner,
            'email' => $this->superMerchant->email,
            'phone' => $this->superMerchant->phone,
            'hp' => $this->superMerchant->hp ?? '',
            'total_tenant' => $total_tenant,
            'masa_aktif' => $this->masa_aktif,
            'limit_cashier' => $limit_cashier,
            'start_date' => (string) $this->created_at,
            'end_date' => (string) $this->end_date,
            'remaining' => $this->remaining,
            'status' => $this->status,
        ];
        return $data;
    }
}
