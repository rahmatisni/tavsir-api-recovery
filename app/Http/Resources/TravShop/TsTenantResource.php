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
            'photo' => $this->photo_url ? asset($this->photo_url) : null,
            'rating' => round($this->rating, 1),
            'total_rating' => $this->total_rating,
            'penilaian' => 200,
            'category' => $this->category_tenant->name ?? '',
            'sub_category' => 'Restaurant',
            'is_open' => $this->is_open == 1 ? true : false,
            // 'is_open' => false,
            'time_end' => $this->time_end,
        ];
    }
}
