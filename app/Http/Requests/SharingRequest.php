<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SharingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nama_pks' => 'required',
            'nomor_pks' => 'required',
            'pengelola_id' => 'required',
            'tenant_id' => 'required|exists:ref_tenant,id',
            'persentase_pengelola' => 'required',
            'persentase_supertenant' => 'required',
            'persentase_tenant' => 'required',
            'waktu_mulai' => 'required|date',
            'waktu_selesai' => 'required|date|after_or_equal:waktu_mulai',
            'status' => 'required|in:sedang_berjalan,belum_berjalan,sudah_berjalan',
            'file' => 'nullable|max:5000',
        ];
    }

    public function attributes()
    {
        return [
            'nama_pks' => 'Nama PKS',
            'nomor_pks' => 'Nomor PKS',
            'tenant_id' => 'Tenant',
            'pengelola_id' => 'Pengelola',
            'persentase_pengelola' => 'Persentase Pengelola',
            'persentase_supertenant' => 'Persentase Supertenant',
            'persentase_tenant' => 'Persentase Tenant',
            'waktu_mulai' => 'Waktu Mulai',
            'waktu_selesai' => 'Waktu Selesai',
            'status' => 'Status',
            'file' => 'File',
        ];
    }
}
