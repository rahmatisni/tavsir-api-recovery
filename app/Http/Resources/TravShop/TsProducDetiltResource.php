<?php

namespace App\Http\Resources\TravShop;

use App\Http\Resources\CustomizeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TsProducDetiltResource extends JsonResource
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
            'category' => $this->category,
            'photo' => $this->photo,
            "price" => $this->price,
            "customize" => CustomizeResource::collection($this->customize),
        ];
    }
}
