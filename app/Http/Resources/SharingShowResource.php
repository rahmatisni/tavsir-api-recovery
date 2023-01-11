<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

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
        return [
            'id' => $this->id,
            'nama_pks' => $this->nama_pks,
            'nomor_pks' => $this->nomor_pks,
            'pengelola_id' => $this->pengelola_id,
            'tenant_id' => $this->tenant_id,
            'is_supertenant' => $this->tenant->is_supertenant,
            'tenant_name' => $this->tenant->name,
            'member_tenant' => $this->tenant->member_tenant->pluck('name'),
            'persentase_pengelola' => $this->persentase_pengelola,
            'persentase_supertenant' => $this->persentase_supertenant,
            'persentase_tenant' => $this->persentase_tenant,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $this->waktu_selesai,
            'status' => $this->status,
            'file' => $this->file ? asset($this->file) : null,
        ];
    }
}
