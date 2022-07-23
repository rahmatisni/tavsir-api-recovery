<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VariantRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'product_id' => 'required|exists:ref_product,id',
            'sub_variant' => 'required|array',
            'sub_variant.*.name' => 'required|string|max:255',
            'sub_variant.*.price' => 'required|numeric|min:0|max:1000000',
        ];
    }
}
