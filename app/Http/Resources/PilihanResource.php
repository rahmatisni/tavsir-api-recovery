<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PilihanResource extends JsonResource
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
            'price' => (int) $this->price,
            'is_available' => (int) $this->is_available,
        ];
    }
}
