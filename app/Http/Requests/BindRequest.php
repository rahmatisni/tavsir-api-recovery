<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BindRequest extends FormRequest
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
            'customer_id' => 'required',
            'sof_code' => 'required',
            'customer_name' => 'required',
            'card_no' => 'required',
            'phone' => 'required',
            'email' => 'required',
        ];
    }
}
