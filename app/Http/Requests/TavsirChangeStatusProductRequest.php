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
