<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomizeRequest extends FormRequest
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
        $id = $this->route('customize')->id ?? 'NULL';
        $tenant_id = $this->route('customize')->tenant_id ?? 0;
        return [
            'tenant_id' => 'required|integer|exists:ref_tenant,id',
            'name' => 'required|string|max:50',
            'pilihan' => 'required|array',
            'pilihan.*.name' => 'required|string|max:20',
            'pilihan.*.price' => 'required|integer|min:0|max:1000000',
            'pilihan.*.is_available' => 'required|boolean',
        ];
    }

    public function attributes()
    {
        return [
            'tenant_id' => 'Tenant',
            'name' => 'Nama',
            'must_choose' => 'Harus Pilih',
            'pilihan' => 'Pilihan',
            'pilihan.*.name' => 'Nama Pilihan',
            'pilihan.*.price' => 'Harga',
            'pilihan.*.is_available' => 'Tersedia',
        ];
    }
}
