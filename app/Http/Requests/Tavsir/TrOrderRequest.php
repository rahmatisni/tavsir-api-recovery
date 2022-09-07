<?php

namespace App\Http\Requests\Tavsir;

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
            'action' => 'required|in:'.TransOrder::ACTION_SAVE.','.TransOrder::ACTION_PAY,
            'pembayaran' => 'min:0|max:10000000|required_if:action,'.TransOrder::ACTION_PAY,
            'id' => 'nullable',
            'product' => 'required|array',
            'product.*.product_id' => 'required|integer|exists:ref_product,id',
            'product.*.customize' => 'nullable|array',
            'product.*.pilihan' => 'nullable|array',
            'product.*.qty' => 'required|integer|min:1|max:99',
            'product.*.note' => 'nullable|string|max:255',
        ];
    }

    public function attributes()
    {
        return [
            'action' => 'Action',
            'pembayaran' => 'Pembayaran',
            'id' => 'ID',
            'product' => 'Product',
            'product.*.product_id' => 'Product',
            'product.*.customize' => 'Customize',
            'product.*.pilihan' => 'Pilihan',
            'product.*.qty' => 'Qty',
            'product.*.note' => 'Note',
        ];
    }

    public function messages()
    {
        return [
            'action.in' => 'Value Action  '.TransOrder::ACTION_SAVE.' or '.TransOrder::ACTION_PAY,
        ];
    }
}
