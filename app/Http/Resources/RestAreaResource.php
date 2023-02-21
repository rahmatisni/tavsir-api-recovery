<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class RestAreaResource extends JsonResource
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
            ...parent::toArray($request),
            'photo' => $this->photo ? asset($this->photo) : null,
            'ruas_name' => $this->ruas->name ?? '',
            'tenant_total' => $this->tenant->count(),
            'detil_tenant' => $this->tenant()->categoryCount()->get()
        ];
    }
}
