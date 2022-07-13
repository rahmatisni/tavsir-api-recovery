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
            'category' => 'required',
            'sku' => 'required',
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'variant.*' => 'required',
            'variant.*.name' => 'required|max:20',
            'variant.*.price' => 'required|numeric|min:100|max:1000000',
            'addon' => 'array',
            'addon.*' => 'required',
            'addon.*.name' => 'required|max:20',
            'addon.*.price' => 'required|numeric|min:100|max:100000',
            'price' => 'required',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:255',
        ];
    }
}
