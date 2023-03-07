<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TsOrderRequest extends FormRequest
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
            'tenant_id' => 'required|exists:ref_tenant,id,deleted_at,NULL',
            'business_id' => 'required|exists:ref_business,id',
            'customer_id' => 'required',
            'merchant_id' => 'required',
            'sub_merchant_id' => 'required',
            'product' => 'required|array',
            'product.*.product_id' => 'required|integer|exists:ref_product,id,deleted_at,NULL',
            'product.*.customize' => 'array',
            'product.*.pilihan' => 'array',
            'product.*.qty' => [
                'required',
                'integer',
                'min:1',
                function($attribute, $value, $fail){
                    $index = explode('.', $attribute)[1];
                    $id = $this->product[$index]['product_id'];
                    $p = Product::find($id);
                    if($p){
                        if ($value > $p->stock) {
                            $fail('The '.$attribute.' is invalid. stock available is '. $p->stock);
                        }
                    }
                }
            ],
            'product.*.note' => 'nullable|string|max:255',
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
