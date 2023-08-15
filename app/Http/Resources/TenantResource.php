<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
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
            'business_owner_name' => $this->business->owner ?? '',
            'business_status_perusahaan' => $this->business->status_perusahaan ?? '',
            'rest_area_id' => $this->rest_area_id,
            'rest_area_name' => $this->rest_area->name ?? '',
            'rest_area_is_open' => $this->rest_area->is_open ?? '',
            'ruas_id' => $this->ruas_id,
            'ruas_name' => $this->ruas->name ?? '',
            'name' => $this->name,
            'category_tenant_id' => $this->category_tenant_id,
            'category_tenant_name' => $this->category_tenant->name ?? '',
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rest_area_id' => $this->rest_area_id,
            'time_start' => $this->time_start,
            'time_end' => $this->time_end,
            'phone' => $this->phone,
            'manager' => $this->manager,
            'photo_url' => $this->photo_url ? asset($this->photo_url) : null,
            'merchant_id' => $this->merchant_id,
            'sub_merchant_id' => $this->sub_merchant_id,
            'is_open' => $this->is_open,
            'is_verified' => $this->is_verified,
            'in_takengo' => $this->in_takengo,
            'is_print' => $this->is_print,
            'rating' => round($this->rating, 1),
            'total_rating' => $this->total_rating,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
