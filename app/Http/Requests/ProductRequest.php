<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'tenant_id' => 'required|exists:ref_tenant,id',
            'category_id' => 'required|exists:ref_category,id',
            'sku' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'photo' => 'nullable|max:5000',
            'price' => 'required|numeric|min:0|max:100000000',
            'is_active' => 'required|boolean',
            'stock' => 'required|numeric|min:1|max:999999999',
            'description' => 'nullable|string|max:255',
            'customize' => 'nullable|array',
            'customize.*.customize_id' => 'nullable|integer|exists:ref_customize,id',
            'customize.*.must_choose' => 'nullable|boolean',
        ];
    }

    public function attributes()
    {
        return [
            'tenant_id' => 'Tenant',
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
