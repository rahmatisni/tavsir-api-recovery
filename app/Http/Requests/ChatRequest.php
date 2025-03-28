<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
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
            'user_type' => 'required|in:customer,tenant',
            'user_id' => 'required',
            'user_name' => 'required',
            'tenant_id' => 'required_if:user_type,tenant',
            'trans_order_id' => 'required|exists:trans_order,id',
            'text' => 'required|string',
            'image' => 'nullable|max:5000',
        ];
    }

    public function messages()
    {
        return [
            'in' => 'customer atau tenant',
        ];
    }
}
