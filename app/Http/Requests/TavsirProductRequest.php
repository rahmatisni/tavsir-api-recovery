<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TavsirProductRequest extends FormRequest
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
            'category_id' => 'required|exists:ref_category,id',
            'sku' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'photo' => 'nullable|max:5000',
            'price' => 'required|numeric|min:100|max:1000000',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:255',
            'customize' => 'nullable|array',
            'customize.*.customize_id' => 'nullable|integer|exists:ref_customize,id',
            'customize.*.must_choose' => 'nullable|boolean',
        ];
    }

    public function attributes()
    {
        return [
            'category_id' => 'Category',
            'sku' => 'SKU',
            'name' => 'Name',
            'photo' => 'Photo',
            'price' => 'Price',
            'is_active' => 'Is Active',
            'description' => 'Description',
            'customize' => 'List Customize',
            'customize.*.customize_id' => 'Customize',
            'customize.*.must_choose' => 'Must Choose',
        ];
    }
}
