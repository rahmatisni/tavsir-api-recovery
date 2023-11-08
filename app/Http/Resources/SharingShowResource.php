<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class SharingShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $now = Carbon::now();

        return [
            'id' => $this->id,
            'nama_pks' => $this->nama_pks,
            'nomor_pks' => $this->nomor_pks,
            'business_id' => $this->business_id,
            'tenant_id' => $this->tenant_id,
            'business_name' => $this->business?->name,
            'tenant_name' => $this->tenant?->name,
            'sharing_code' => is_array($this->sharing_code) ? ($this->sharing_code) : json_decode($this->sharing_code),
            'sharing_config' => json_decode($this->sharing_config),
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $this->waktu_selesai,
            'status' => $this->status,
            'status_code' => $this->status_code ?? '',
            'file' => $this->file ? asset($this->file) : null,
        ];
    }
}
