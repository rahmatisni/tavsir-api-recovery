<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class DocumentSubscriptionRequest extends FormRequest
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
        $rule['document_type'] = 'required|in:' . Subscription::PKS . ',' . Subscription::BUKTI_BAYAR;
        $rule['file'] = 'required|max:5000';

        return $rule;
    }

    public function messages()
    {
        return [
            'document_type.in' => 'Type must be ' . Subscription::PKS . ' or ' . Subscription::BUKTI_BAYAR,
        ];
    }
}
