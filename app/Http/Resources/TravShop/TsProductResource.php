<?php

namespace App\Http\Resources\TravShop;

use App\Http\Resources\CustomizeResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TsProductResource extends JsonResource
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
            'photo' => $this->photo ? asset($this->photo) : null,
            "price" => $this->price,
            "stock" => $this->stock,
            "is_active" => $this->is_active,
            "category" => $this->category->name ?? '',
            "description" => $this->description,
            "customize" => CustomizeResource::collection($this->customize),
        ];
    }
}
