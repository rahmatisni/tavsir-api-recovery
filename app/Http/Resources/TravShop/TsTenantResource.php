<?php

namespace App\Http\Resources\TravShop;

use Illuminate\Http\Resources\Json\JsonResource;

class TsTenantResource extends JsonResource
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
            'photo' => $this->photo_url,
            'rating' => 4.5,
            'penilaian' => 200,
            'category' => $this->category,
            'sub_category' => 'Restaurant',
            'is_open' => true,
            'time_end' => $this->time_end,
        ];
    }
}
