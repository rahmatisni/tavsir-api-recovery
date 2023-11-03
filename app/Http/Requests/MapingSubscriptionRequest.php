<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MapingSubscriptionRequest extends FormRequest
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
        $rule = [
            'id' => 'required',
            'status' => 'required|in:true,false',
        ];

        if(auth()->user()->role == 'OWNER')
        {
            $rule['tenant_id'] = 'required|exists:ref_tenant,id,business_id,'.auth()->user()->business_id;
        }

        return $rule;
    }
}
