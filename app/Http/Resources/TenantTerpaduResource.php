<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantTerpaduResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $data =  [
            'id' => $this->id,
            'tenant_photo' => $this->tenant->photo_url,
            'name' => $this->name,
            'business_id' => $this->business_id,
            'rest_area_name' => $this->rest_area->name ?? '',
            'is_supertenant' => $this->is_supertenant,
        ];

        if($this->additional){
            $search = $this->additional['search'] ?? null;
            $member = Tenant::where('supertenant_id', $this->id)->myWhereLike('name', $search)->get();
            $data['member'] = TenantTerpaduChildResource::collection($member);
        }

        return $data;
    }
}
