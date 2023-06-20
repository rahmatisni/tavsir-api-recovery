<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CashierTenantResource extends JsonResource
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
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'is_subscription' => $this->is_subscription,
        ];
        return $data;
    }
}
