<?php

namespace App\Http\Requests;

use App\Models\TransOrder;
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
            'voucher' => 'required_if:payment_method_id,3|max:50',
            'cash' => 'required_if:payment_method_id,2|max:10000000|min:0',
            'consume_type' => 'required|in:' . TransOrder::DINE_IN . ',' . TransOrder::TAKE_AWAY,
            'nomor_name' => 'required',
            'bank_id' => 'required_if:payment_method_id,1|exists:ref_bank,id',
            'card_nomor' => 'required_if:payment_method_id,1',
            'ref_nomor' => 'required_if:payment_method_id,1',
        ];
    }

    public function message()
    {
        return [
            'consume_type.in' => 'Value must be ' . TransOrder::DINE_IN . ' or ' . TransOrder::TAKE_AWAY,
        ];
    }
}
