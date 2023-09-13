<?php

namespace App\Http\Requests;

use App\Models\Constanta\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class RawProductUpdateRequest extends FormRequest
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
            'category_id' => 'required|exists:ref_category,id,tenant_id,'.auth()->user()->tenant_id.',type,'.ProductType::BAHAN_BAKU,
            'sku' => 'required|string|max:50|unique:ref_product,sku,'.request()->id.',id,deleted_at,NULL,type,'.ProductType::BAHAN_BAKU.',tenant_id,'.auth()->user()->tenant_id,
            'name' => 'required|string|max:50',
            'photo' => 'nullable|max:5000',
            'stock_min' => 'required|numeric|min:1|max:999999999',
            'is_active' => 'required|boolean',
            'is_notification' => 'required|boolean',
            'description' => 'nullable|string|max:255',
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
            'is_notification' => 'Is Notification',
            'stock' => 'Stock',
            'stock_min' => 'Minimal Stok',
            'satuan_id' => 'Satuan',
            'description' => 'Description',
        ];
    }
}
