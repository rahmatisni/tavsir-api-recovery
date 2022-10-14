<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListInvoiceResource extends JsonResource
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
            'rest_area_name' => $this->rest_area->name ?? '',
            'tenant_name' => $this->tenant->name ?? '',
            'saldo' => $this->saldo,
            'detil' => ListTransInvoiceResource::collection($this->trans_invoice)
        ];
    }
}
