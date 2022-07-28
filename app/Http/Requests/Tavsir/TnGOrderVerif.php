<?php

namespace App\Http\Requests\Tavsir;

use Illuminate\Foundation\Http\FormRequest;

class TnGOrderVerif extends FormRequest
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
            'code_verif' => 'required',
        ];
    }
}
