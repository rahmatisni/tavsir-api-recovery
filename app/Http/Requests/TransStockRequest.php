<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransStockRequest extends FormRequest
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
            'product_id' => 'required|exists:ref_product,id',
            'stock' => 'required|numeric|min:1|max:999',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    public function attributes()
    {
        return [
            'product_id' => 'Product',
            'stock' => 'Stok',
            'keterangan' => 'Keterangan',
        ];
    }
}
