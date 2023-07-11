<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanRekapTransaksiResource extends JsonResource
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
            'start_date' => (string) $this->start_date,
            'end_date' => (string) $this->end_date,
            'periode' => $this->periode,
            'rp_total' => $this->trans_cashbox->rp_total ?? 0,
            'rp_addon_total' => $this->trans_cashbox->rp_addon_total ?? 0,
        ];
    }
}
