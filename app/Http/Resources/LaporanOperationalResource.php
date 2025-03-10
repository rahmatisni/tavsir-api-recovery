<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanOperationalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $sharing = $this?->trans_cashbox?->sharing ?? '{}';
        $sharing = json_decode($sharing, true);
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant->name ?? '',
            'periode' => $this->periode,
            'waktu_buka' => $this->start_date->format('Y-m-d H:i'),
            'waktu_tutup' => $this->end_date->format('Y-m-d H:i'),
            'waktu_rekap' => $this->end_date->format('Y-m-d H:i'),
            'kasir' => $this->cashier->name ?? '',
            'uang_kembalian' => $this->trans_cashbox->inital_cashbox ?? 0,
            'qr' => $this->trans_cashbox->rp_tav_qr ?? 0,
            'digital' => $this->trans_cashbox->total_digital ?? 0,
            'tunai' => $this->trans_cashbox->rp_cash ?? 0,
            'nominal_tunai' => $this->trans_cashbox->cashbox ?? 0,
            'koreksi' => $this->trans_cashbox->pengeluaran_cashbox ?? 0,
            'selisih' => $this->trans_cashbox->different_cashbox ?? 0,
            'keterangan_koreksi' => $this->trans_cashbox->description ?? '',
            'total_addon' => $this->trans_cashbox->rp_addon_total ?? '',
            'total_rekap' => $this->trans_cashbox->rp_total ?? '',
            'trans_cashbox' => $this->trans_cashbox,
            'sharing' => $sharing,
        ];
    }
}
