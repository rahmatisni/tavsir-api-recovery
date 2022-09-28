<?php

namespace App\Http\Resources\Tavsir;

use Illuminate\Http\Resources\Json\JsonResource;

class TnGOrderResource extends JsonResource
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
            'order_id' => $this->order_id,
            'order_date' => $this->created_at,
            'sub_total' => $this->sub_total,
            'discount'=> $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'jumlah_menu' => $this->detil->count(),
        ];
    }
}
