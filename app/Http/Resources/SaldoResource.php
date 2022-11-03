<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SaldoResource extends JsonResource
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
            'nama_lengkap' => $this->nama_lengkap,
            'username' => $this->username,
            'customer_id' => $this->customer_id,
            'phone' => $this->phone,
            'rest_area_id' => $this->rest_area_id,
            'rest_area_name' => $this->rest_area->name ?? '',
            'balance' => $this->balance,
            'qr_code_use' => $this->qr_code_use,
            'is_active' => $this->is_active,
            'qr_code_image' => env('PAYSTATION_URL') . '/storage/qrcode_pelanggan/' . $this->qr_code_image,
            'balance_history' => $this->balance_history,
        ];
    }
}
