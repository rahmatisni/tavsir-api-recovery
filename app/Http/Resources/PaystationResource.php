<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaystationResource extends JsonResource
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
            'rest_area_name' => $this->rest_area->name ?? '',
            'rest_area_latitude' => $this->rest_area->latitude ?? '',
            'rest_area_longitude' => $this->rest_area->longitude ?? ''
        ];
    }
}
