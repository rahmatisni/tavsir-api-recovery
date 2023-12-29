<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenantResiSettingResource extends JsonResource
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
            "id" => $this->id,
            "supertenant_id" => $this->supertenant_id,
            "business_id" => $this->business_id,
            "ruas_id" => $this->ruas_id,
            "name" => $this->name,
            "address" => $this->address,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude,
            "rest_area_id" => $this->rest_area_id,
            "time_start" => $this->time_start,
            "time_end" => $this->time_end,
            "phone" => $this->phone,
            "manager" => $this->manager,
            "photo_url" => $this->photo_url,
            "merchant_id" => $this->merchant_id,
            "sub_merchant_id" => $this->sub_merchant_id,
            "is_open" => $this->is_open,
            "created_by" => $this->created_by,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "deleted_at" => $this->deleted_at,
            "category_tenant_id" => $this->category_tenant_id,
            "email" => $this->email,
            "kuota_kasir" => $this->kuota_kasir,
            "is_subscription" => $this->is_subscription,
            "is_verified" => $this->is_verified,
            "in_takengo" => $this->in_takengo,
            "in_selforder" => $this->in_selforder,
            "is_print" => $this->is_print,
            "is_scan" => $this->is_scan,
            "is_composite" => $this->is_composite,
            "list_payment" => $this->list_payment,
            "list_payment_bucket" => $this->list_payment_bucket2,
            "sharing_code" => $this->sharing_code,
            "sharing_config" => $this->sharing_config,
            "url_self_order" => $this->url_self_order,
            "kategori" => $this->kategori,
            "logo" => $this->logo ? asset($this->logo) : null,
            "additional_information" => $this->additional_information,
            "instagram" => $this->instagram,
            "facebook" => $this->facebook,
            "website" => $this->website,
            "note" => $this->note,
            "business_name" => $this->business->name ?? null,
        ];
    }
}
