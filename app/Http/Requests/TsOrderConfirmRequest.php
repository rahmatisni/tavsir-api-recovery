<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TsOrderConfirmRequest extends FormRequest
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
            'detil_id' => 'required|array',
            'detil_id.*' => 'exists:trans_order_detil,id',
        ];
    }

    public function attributes()
    {
        return [
            'detil_id' => 'Product',
            'detil_id.*' => 'Product Confirmation'
        ];
    }
}
