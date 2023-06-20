<?php

namespace App\Http\Requests;

use App\Models\Jmrb;
use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionRequest extends FormRequest
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
        $rule['type'] = 'required|in:' . Subscription::JMRB . ',' . Subscription::OWNER;
        $rule['document_type'] = 'required|in:' . Subscription::PKS . ',' . Subscription::BUKTI_BAYAR;
        $rule['file'] = 'required|max:5000';
        if ($this->type == Subscription::JMRB) {
            $rule['pic'] = 'required|string|max:255';
            $rule['hp'] = 'required|string|max:255';
            $rule['phone'] = 'required|string|max:255';
            $rule['email'] = 'required|email|max:255';
        }

        if ($this->type == Subscription::OWNER) {
            $rule['business_id'] = [
                'required',
                'exists:ref_business,id',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($this->type == Subscription::OWNER) {
                        $sub = Subscription::where('super_merchant_id', $value)->orderBy('id', 'desc')->first();
                        if (!$sub) {
                            return true;
                        }
                        $fail('Subscription already create');
                    }
                },
            ];
            $rule['masa_aktif'] = 'required_if:type,' . Subscription::OWNER . '.|integer|min:1|max:12';
        }

        return $rule;
    }

    public function messages()
    {
        return [
            'type.in' => 'Type must be ' . Subscription::JMRB . ' or ' . Subscription::OWNER,
            'document_type.in' => 'Type must be ' . Subscription::PKS . ' or ' . Subscription::BUKTI_BAYAR,
        ];
    }
}
