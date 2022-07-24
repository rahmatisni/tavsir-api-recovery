<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TrCartProductResource extends JsonResource
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
            "product_id" => $this->product_id,
            "product_name" => $this->product_name,
            "variant" => $this->variant,
            //"variant" => TrVariantResource::collection($this->variant),
            "addon" => $this->addon,
            "qty" => $this->qty,
            "price" => $this->price,
            "total_price" => $this->total_price,
            "note" => $this->note,
            "photo" => $this->photo,
        ];
    }
}
