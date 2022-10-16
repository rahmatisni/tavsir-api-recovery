<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RekapResource extends JsonResource
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
            'periode' => $this->periode,
            'cashier_name' => $this->cashier->name ?? '',
            'rp_total' => $this->trans_cashbox->rp_total ?? 0,
            'start_date' => (string) $this->start_date,
            'metode_tunai' => [
                'total_tunai' => $this->trans_cashbox->rp_cash ?? 0,
                'uang_kembalian' => $this->trans_cashbox->initial_cashbox ?? 0,
                'selisih' => $this->trans_cashbox->different_cashbox ?? 0,
                'nominal_koreksi' => $this->trans_cashbox->pengeluaran_cashbox ?? 0,
                'keterangan' => $this->trans_cashbox->description ?? '',
            ],
            'metode_qr' => $this->trans_cashbox->rp_qr ?? 0,
            'metode_digital' => [
                'total_digital' => $this->trans_cashbox->total_digital ?? 0,
                'rp_va_bri' => $this->trans_cashbox->rp_va_bri ?? 0,
                'rp_dd_bri' => $this->trans_cashbox->rp_dd_bri ?? 0,
                'rp_va_mandiri' => $this->trans_cashbox->rp_va_mandiri ?? 0,
                'rp_va_bni' => $this->trans_cashbox->rp_va_bni ?? 0,
            ],
        ];
    }
}
