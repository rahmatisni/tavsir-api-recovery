<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'business_name' => $this->business->name ?? '',
            'owner_name' => $this->business->owner ?? '',
            'owner_phone' => $this->business->phone ?? '',
            'owner_email' => $this->business->email ?? '',
            'jumlah_tenant' => $this->business->tenant()->count() ?? 0,
            'masa_aktif' => $this->masa_aktif,
            'limit_tenant' => $this->limit_tenant,
            'limit_cashier' => $this->limit_cashier,
            'created_at' => (string) $this->created_at,
            'end_at' => (string) $this->created_at->addMonths($this->masa_aktif),
            'remaining active' => $this->created_at->addMonths($this->masa_aktif)->diffInDays(now()),
            'status' => $this->status,
        ];
    }
}
