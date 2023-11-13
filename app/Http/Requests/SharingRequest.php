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
            // 'business_id' => 'required',
            'tenant_id' => 'required|exists:ref_tenant,id',
            'sharing_code' => 'required',
            'sharing_config' => 'required',
            // 'waktu_mulai' => 'required|date|after_or_equal:now',
            // 'waktu_selesai' => 'required|date|after_or_equal:waktu_mulai',
            // 'status' => 'required|in:sedang_berjalan,belum_berjalan,sudah_berjalan',
            // 'file' => 'nullable|max:5000|mimes:pdf',
        ];
    }

    public function attributes()
    {
        return [
            'nama_pks' => 'Nama PKS',
            'nomor_pks' => 'Nomor PKS',
            'tenant_id' => 'Tenant',
            'sharing_code' => 'Persentase Pengelola',
            'business_id' => 'Bisnis ID',
            // 'persentase_supertenant' => 'Persentase Supertenant',
            'sharing_config' => 'Persentase Tenant',
            'waktu_mulai' => 'Waktu Mulai',
            'waktu_selesai' => 'Waktu Selesai',
            'status' => 'Status',
            'file' => 'File',
        ];
    }
}
