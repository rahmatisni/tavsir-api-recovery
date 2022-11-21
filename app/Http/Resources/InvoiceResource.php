<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'claim_date' => (string)$this->claim_date,
            'paid_date' => (string)$this->paid_date,
            'tenant' => $this->trans_saldo->tenant->name ?? '',
            'rest_area' => $this->trans_saldo->rest_area->name ?? '',
            'nominal' => $this->nominal,
            'cashier_name' => $this->cashier->name ?? '',
            'no_invoice' => $this->invoice_id,
            'no_kwitansi' => $this->kwitansi_id,
            'saldo_tersimpan' => $this->trans_saldo->saldo ?? 0,
            'pay_station_name' => $this->pay_station->name ?? '',
            'pay_petugas_name' => $this->pay_petugas->name ?? '',
        ];
    }
}
