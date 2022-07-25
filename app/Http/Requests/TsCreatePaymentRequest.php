<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TsCreatePaymentRequest extends FormRequest
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
            'payment_method_id' => 'required|exists:ref_payment_method,id',
            'customer_phone' => 'required|string|max:15',
            'customer_name' => 'required|string|max:50',
            'customer_email' => 'required|string|max:50',
        ];
    }
}
