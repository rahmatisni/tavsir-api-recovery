<?php

namespace App\Http\Requests;

use App\Models\TransOrder;
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
            'business_id' => 'nullable|exists:ref_business,id',
            'payment_method_id' => 'nullable|exists:ref_payment_method,id',
            'order_type' => 'nullable|in:' . TransOrder::ORDER_TAKE_N_GO . ',' . TransOrder::ORDER_TAVSIR
        ];
    }

    public function messages()
    {
        return [
            'order_type.in' =>  'The selected :attribute is invalid. Value must be ' . TransOrder::ORDER_TAKE_N_GO . ' or ' . TransOrder::ORDER_TAVSIR,
        ];
    }
}
