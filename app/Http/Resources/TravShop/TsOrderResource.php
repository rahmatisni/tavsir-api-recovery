<?php

namespace App\Http\Resources\TravShop;

use Illuminate\Http\Resources\Json\JsonResource;

class TsOrderResource extends JsonResource
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
            'rest_area_name' => $this->tenant ? ($this->tenant->rest_area ? $this->tenant->rest_area->name : null) : null,
            'tenant_name' => $this->tenant->name ?? null,
            'order_id' => $this->order_id,
            'order_type' => $this->order_type,
            'sub_total' => $this->sub_total,
            'fee' => $this->fee,
            'service_fee' => $this->service_fee,
            'total' => $this->total,
            'status' => $this->status,
            'casheer_name' => $this->casheer->name ?? '',
            'total_pesanan' => $this->detil->count(),
            'created_at' => $this->created_at,
            'detil' => TsOrderDetilResource::collection($this->detil),
        ];
    }
}
