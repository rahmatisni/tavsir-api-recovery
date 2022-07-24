<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TrVariantResource extends JsonResource
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
            "id" => $this->id,
            'name' => $this->name,
            'sub_variant' => $this->sub_variant,
        ];
    }
}
