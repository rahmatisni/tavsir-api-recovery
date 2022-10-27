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
        $rule['aktif_awal'] = 'required|date_format:Y-m-d';
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
                        $sub_aktif = $sub->created_at->addMonths($this->masa_aktif);
                        if ($sub_aktif >= now()) {
                            $remaining_active = $sub->created_at->addMonths($this->masa_aktif)->diffInDays(now());
                            if ($remaining_active > 0) {
                                $this->aktif_awal = $sub_aktif;
                            }
                            // $fail('Subscription is still ' . $remaining_active . ' day');
                        }
                    }
                },
            ];
            $rule['masa_aktif'] = 'required_if:type,' . Subscription::OWNER . '.|integer|min:1|max:12';
            $rule['limit_cashier'] = 'required|integer|min:1';
        }

        return $rule;
    }

    public function messages()
    {
        return [
            'type.in' => 'Type must be ' . Subscription::JMRB . ' or ' . Subscription::OWNER,
        ];
    }
}
