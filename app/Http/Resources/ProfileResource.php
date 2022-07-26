<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? '',
            'business_id' => $this->business_id,
            'rest_area_id' => $this->rest_area_id,
            'paystation_id' => $this->paystation_id,
            'jabatan' => 'Karyawan',
            'role' => $this->role,
            'business_id' => $this->business_id,
            'paystation_id' => $this->paystation_id,
            'rest_area_id' => $this->rest_area_id,
        ];
    }
}
