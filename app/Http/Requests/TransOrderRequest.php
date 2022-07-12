<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransOrderRequest extends FormRequest
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
        [
            'sub_total' => 'required',
            'fee' => 'required',
            'total' => 'required',
            'business_id' => 'required|exists:ref_business,id',
            'tenant_id' => 'required|exists:ref_tenant,id',
            'customer_id' => 'required|exists:ref_customer,id',
            'voucher_id' => 'nullable|exists:ref_voucher,id',
            'payment_method_id' => 'required|exists:ref_payment_method,id',
            'payment_id' => 'required|exists:ref_payment,id',
            'discount' => 'nullable',
        ];
    }
}
