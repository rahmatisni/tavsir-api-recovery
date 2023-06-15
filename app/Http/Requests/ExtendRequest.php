<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class ExtendRequest extends FormRequest
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
        // $rule['document_type'] = 'required|in:' . Subscription::PKS . ',' . Subscription::BUKTI_BAYAR;
        $rule['aktif_awal'] = 'required|date_format:Y-m-d';
        $rule['limit_tenant'] = 'min:1';
        $rule['limit_cashier'] = 'min:1';

        return $rule;
    }

    // public function messages()
    // {
    //     return [
    //         'document_type.in' => 'Type must be ' . Subscription::PKS . ' or ' . Subscription::BUKTI_BAYAR,
    //     ];
    // }
}
