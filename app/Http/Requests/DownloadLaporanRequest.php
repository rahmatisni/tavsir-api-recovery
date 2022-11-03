<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadLaporanRequest extends FormRequest
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
            'tanggal_awal' => 'nullable|dateFormat:Y-m-d',
            'tanggal_akhir' => 'nullable|dateFormat:Y-m-d',
            'tenant_id' => 'nullable|exists:ref_tenant,id',
            'rest_area_id' => 'nullable|exists:ref_rest_area,id',
            'business_id' => 'nullable|exists:ref_business,id'
        ];
    }
}
