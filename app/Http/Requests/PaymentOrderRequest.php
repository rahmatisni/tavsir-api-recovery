<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentOrderRequest extends FormRequest
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
            'voucher' => 'required_if:payment_method_id,5|max:50',
            'cash' => 'number|required_if:payment_method_id,6|max:10000000|min:0',
        ];
    }
}
