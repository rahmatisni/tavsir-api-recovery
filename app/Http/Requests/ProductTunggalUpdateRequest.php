<?php

namespace App\Http\Requests;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductTunggalUpdateRequest extends FormRequest
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
        $rule = [
            'category_id' => 'required|exists:ref_category,id,tenant_id,'.auth()->user()->tenant_id.',type,'.ProductType::PRODUCT,
            // 'satuan_id' => 'required|exists:ref_satuan,id',
            'sku' => 'required|string|max:50|unique:ref_product,sku,NULL,id,deleted_at,'.$this->id.',tenant_id,'.auth()->user()->tenant_id.',type,'.ProductType::PRODUCT,
            'name' => 'required|string|max:50',
            'photo' => 'nullable|max:5000',
            'price' => 'required|numeric|min:0|max:999999999',
            'is_active' => 'required|boolean',
            'is_notification' => 'required|boolean',
            'stock_min' => 'required_if:is_composit,0||min:1|max:999999999',
            'description' => 'nullable|string|max:255',

            //Custome Product
            'customize' => 'nullable|array',
            'customize.*.customize_id' => 'nullable|integer|exists:ref_customize,id,tenant_id,'.auth()->user()->tenant_id,
            'customize.*.must_choose' => 'nullable|boolean',
        ];

        $product = Product::find($this->id);

        if($product->is_composit == 1){
            $rule['raw'] = 'required|array';
            $rule['raw.*.child_id'] = 'integer|exists:ref_product,id,type,'.ProductType::BAHAN_BAKU.',tenant_id,'.auth()->user()->tenant_id;
            $rule['raw.*.qty'] = 'integer|min:1|max:999';
        }

        return $rule;
    }

    public function attributes()
    {
        return [
            'is_composit' => 'Product Composit',
            'tenant_id' => 'Tenant',
            'category_id' => 'Category',
            'sku' => 'SKU',
            'name' => 'Name',
            'photo' => 'Photo',
            'price' => 'harga',
            'price_capital' => 'Harga Modal',
            'is_active' => 'Is Active',
            'is_notification' => 'Notification',
            'description' => 'Description',
            'stock_min' => 'Stok Minimal',
            'stock' => 'Stok',
            'customize' => 'List Customize',
            'customize.*.customize_id' => 'Customize',
            'customize.*.must_choose' => 'Must Choose',
            'raw' => 'List bahan baku',
            'raw.*.raw_product_id' => 'Prodcut bahan baku',
            'raw.*.qty' => 'Qty',
        ];
    }
}
