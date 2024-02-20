<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodRuleRequest extends FormRequest
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
        $rule =  [
            'payment_method_id' => 'required|exists:ref_payment_method,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ];
        if($this->id){
            unset($rule['payment_method_id']);
        }
        return $rule;
    }
}
