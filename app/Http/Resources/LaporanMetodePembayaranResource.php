<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LaporanMetodePembayaranResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // return $this->map(function ($item, $key) {
        return [
            // 'metode_pembayaran' => $this['qty'],
            'jumlah_transalsi' => $this['qty'],
            'total_transaksi' => $this['total']
        ];
        // });
    }
}
