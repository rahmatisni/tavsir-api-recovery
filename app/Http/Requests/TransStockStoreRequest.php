<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransStockStoreRequest extends FormRequest
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
            'product_id' => 'integer|exists:ref_product,id,tenant_id,'.auth()->user()->tenant_id.',deleted_at,NULL',
            'stock' => 'required|numeric|min:1|max:999',
            'price_capital' => 'required|numeric|min:0|max:100000000',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    public function attributes()
    {
        return [
            'product_id' => 'Product',
            'stock' => 'Stok',
            'keterangan' => 'Keterangan',
            'price_capital' => 'Harga Modal',
        ];
    }
}
