<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RawProductAddStockRequest extends FormRequest
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
            'stock' => 'required|numeric|min:1|max:999',
            'price' => 'required|numeric|min:0|max:100000000',
        ];
    }

    public function attributes()
    {
        return [
            'stock' => 'Stock',
            'price' => 'Price',
        ];
    }
}
