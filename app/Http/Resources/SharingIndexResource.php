<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
class SharingIndexResource extends JsonResource
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
            // 'pengelola_id' => $this->pengelola_id,
            // 'pengelola_name' => $this->pengelola?->name,
            // 'supertenant_id' => $this->supertenant_id,
            // 'supertenant_name' => $this->supertenant?->name,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant?->name,
            // 'member_tenant' => $this->tenant->member_tenant->pluck('name'),
            'sharing_code' => is_array($this->sharing_code) ? ($this->sharing_code) : json_decode($this->sharing_code),
            'sharing_config' => json_decode($this->sharing_config),
            // 'persentase_tenant' => $this->persentase_tenant,
            'waktu_mulai' => $this->waktu_mulai,
            'waktu_selesai' => $this->waktu_selesai,
            // 'status' => $this->status ,
            'status' => $this->status === 'sudah_berakhir' ? 'sudah_berakhir' : ($now->between($this->waktu_mulai, $this->waktu_selesai) && $this->status === 'belum_berjalan' ? 'sedang_berjalan' : (($now > $this->waktu_selesai)? 'sudah_berakhir':'belum_berjalan')),
            'file' => $this->file ? asset($this->file) : null,
        ];
    }
}
