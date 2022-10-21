<?php

namespace App\Http\Requests;

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
        return [
            'business_id' => [
                'required',
                'integer',
                'exists:ref_business,id',
                function($attribute, $value, $fail) {
                    $sub = Subscription::where('business_id',$value)->orderBy('id', 'desc')->first();
                    if(!$sub) return true;
                    $sub_aktif = $sub->created_at->addMonths($this->masa_aktif);
                    if($sub_aktif >= now()) {
                        $remaining_active = $sub->created_at->addMonths($this->masa_aktif)->diffInDays(now());
                        $fail('Subscription is still '.$remaining_active.' day');
                    }
                },
            ],
            'masa_aktif' => 'required|integer|min:1|max:12',
            'limit_tenant' => 'required|integer|min:1',
            'limit_cashier' => 'required|integer|min:1',
        ];
    }
}
