<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TrProductResource extends JsonResource
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
            'variant'=> TrVariantResource::collection($this->variant),
            'addon'=> $this->addon,
            'photo' => $this->photo,
            "discount" => $this->discount,
            "price" => $this->price,
            "is_active" => $this->is_active,
        ];
    }
}
