<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
            $limit_tenant = $this->limit_tenant ?? 0;
            $total_tenant = $this->superMerchant->tenant()->count() ?? 0;
            $total_cashear = $this->superMerchant?->tenant()->count();
        } else {
            $owner = $this->superMerchant->pic;
            $limit_cashier = 'Unlimited';
            $total_tenant = 'Unlimited';
        }

        $all = Subscription::where('super_merchant_id', $this->super_merchant_id)->get();
        $count_limit_tenant = 0;
        $count_limit_cashier = 0;
        foreach ($all as $key => $value) {
            $count_limit_tenant += $value->limit_tenant;
            $count_limit_cashier += $value->limit_cashier;
        }
        $data =  [
            'id' => $this->id,
            'id_activation' => $this->id_activation,
            'document_type' => $this->document_type,
            'file' => $this->file ? url(Storage::url($this->file)) : null,
            'type' => $this->type,
            'super_merchant_id' => $this->super_merchant_id,
            'super_merchant' => $this->superMerchant?->name,
            'owner' => $owner,
            'email' => $this->superMerchant->email,
            'phone' => $this->superMerchant->phone,
            'hp' => $this->superMerchant->hp ?? '',
            'total_tenant' => $total_tenant,
            'total_cashear' => $total_cashear,
            'masa_aktif' => $this->masa_aktif,
            'limit_cashier' => $limit_cashier,
            'limit_tenant' => $limit_tenant,
            'start_date' => (string) $this->created_at,
            'end_date' => (string) $this->end_date,
            'remaining' => $this->remaining,
            'status_aktivasi' => $this->status_aktivasi,
            'detail_aktivasi' => $this->detail_aktivasi,
            'count_limit_tenant' => $count_limit_tenant,
            'count_limit_cashier' => $count_limit_cashier,
        ];
        return $data;
    }
}
