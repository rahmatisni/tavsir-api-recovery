<?php

namespace App\Http\Requests\Tavsir;

use App\Models\Constanta\ProductType;
use App\Models\Product;
use App\Models\TransOrder;
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
            'id' => 'nullable',
            'product' => 'required|array',
            'product.*.product_id' => 'required|integer|exists:ref_product,id,is_active,1,type,'.ProductType::PRODUCT.',deleted_at,NULL',
            // 'product.*.product_id' => 'required|integer|exists:ref_product,id,is_active,1,type,'.ProductType::PRODUCT.',tenant_id,'.auth()->user()->tenant_id.',deleted_at,NULL',
            'product.*.customize' => 'array',
            'product.*.pilihan' => 'array',
            'product.*.note' => 'nullable|string|max:100',
            'product.*.qty' => [
                'required',
                'integer',
                'min:1',
                function($attribute, $value, $fail){
                    $index = explode('.', $attribute)[1];
                    $id = $this->product[$index]['product_id'];
                    $p = Product::byType(ProductType::PRODUCT)->find($id);
                    if($p){
                        if ($value > $p->stock) {
                            $fail('The '.$attribute.' is invalid. stock available is '. $p->stock);
                        }
                    }
                }
            ],
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'ID',
            'product' => 'Product',
            'product.*.product_id' => 'Product',
            'product.*.customize' => 'Customize',
            'product.*.pilihan' => 'Pilihan',
            'product.*.qty' => 'Qty',
            'product.*.note' => 'Note',
        ];
    }
}
