<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class SubscriptionChangeStatusRequest extends FormRequest
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
            'status' => 'required|in:'.Subscription::ACTIVE.','.Subscription::INACTIVE,
        ];
    }

    public function messages()
    {
        return [
            'status.in' => 'Status must be '.Subscription::ACTIVE.' or '.Subscription::INACTIVE,
        ];
    }
}
