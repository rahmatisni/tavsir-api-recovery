<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherRequest extends FormRequest
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
        //return [];
        return [
            'voucher_uuid' => 'required',
            'customer_id' => 'required|string',
            'phone' => 'required|string',
            //'trx_id' => 'required|integer',
            'balance' => 'required|string',
            'qr' => 'required|string',
            'auth_id' => 'required|string',
            'paystation_id' => 'required|integer',
        ];
    }
}
