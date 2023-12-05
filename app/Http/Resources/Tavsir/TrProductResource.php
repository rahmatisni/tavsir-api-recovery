<?php

namespace App\Http\Resources\Tavsir;

use App\Http\Resources\CustomizeResource;
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

        $cek_product_have_not_active = $this->trans_product_raw->where('is_active',0)->count();
        $stock = $this->stock;
        if($this->is_composit == 1){
            if($cek_product_have_not_active > 0){
                $stock = 0;
            }
        }

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'tenant_name' => $this->tenant?->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name ?? '',
            'photo' => $this->photo ? asset($this->photo) : null,
            'discount' => $this->discount,
            'price' => $this->price,
            'stock' => $stock,
            'is_active' => $this->is_active,
            'is_composite' => $this->is_composit,
            'description' => $this->description,
            'customize' => CustomizeResource::collection($this->customize),
        ];
    }
    
}
