<?php

namespace App\Http\Resources\Pos;

use Illuminate\Http\Resources\Json\JsonResource;

class DropDownResource extends JsonResource
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
            "tenant_id" => $this->tenant_id,
            "name" => $this->name,
            "sku" =>  $this->sku,
            "category_id" => $this->category_id,
            "photo" => $this->photo,
            "discount" => $this->discount,
            "price" => $this->price,
            "is_active" => $this->is_active,
            "description" => $this->description,
            "stock" => $this->description,
            "is_composit" => $this->is_composit,
            "type" =>  $this->type,
            "price_capital" => $this->price_capital,
            "satuan_id" => $this->satuan_id,
            "category_name" => $this->category?->name,
            "satuan_type" => $this->satuan?->type,
            "satuan_name" => $this->satuan?->name,

        ];
    }
}
