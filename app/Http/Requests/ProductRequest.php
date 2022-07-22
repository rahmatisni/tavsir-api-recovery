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
            'category' => 'required|string|max:20',
            'sku' => 'required|string|max:20',
            'name' => 'required|string|max:50',
            'photo' => 'nullable|image|max:2048',
            'price' => 'required|numeric|min:100|max:1000000',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:255',
        ];
    }
}
