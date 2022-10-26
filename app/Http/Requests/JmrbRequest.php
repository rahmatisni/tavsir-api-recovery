<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JmrbRequest extends FormRequest
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
            'pic' => 'required|string|max:255',
            'email' => 'required|email|string|max:255',
            'hp' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ];
    }
}
