<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;



class ListInvoiceResourceDerek extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'kwitansi_id' => $this->kwitansi_id,
            'nominal' => $this->nominal,
            'terbilang '=> '',
            'claim_date' => $this->claim_date,
            'claimed_by' => $this->cashier?->name, 
            'paid_date' => $this->paid_date,
            'paid_by' => $this->petugas?->name,
            'status' => $this->status,
            'file' => $this->file ? url(Storage::url($this->file)) : null,

        ];
    }
    
}
