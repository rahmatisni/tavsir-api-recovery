<?php

namespace App\Http\Requests\Tavsir;

use Illuminate\Foundation\Http\FormRequest;

class TrOrderRequest extends FormRequest
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
            'id' => 'required',
            'product' => 'required|array',
            'product.*.product_id' => 'required|integer|exists:ref_product,id',
            'product.*.customize' => 'nullable|array',
            'product.*.pilihan' => 'nullable|array',
            'product.*.qty' => 'required|integer|min:1|max:99',
            'product.*.note' => 'nullable|string|max:255',
        ];
    }
}
