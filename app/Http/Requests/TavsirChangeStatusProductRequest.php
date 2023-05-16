<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TavsirChangeStatusProductRequest extends FormRequest
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
            'product_id' => 'required|array',
            'is_active' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'product_id' => 'Product',
            'is_active' => 'Is Active',
        ];
    }
}
