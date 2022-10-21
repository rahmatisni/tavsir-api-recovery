<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class TenantRequest extends FormRequest
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
                    $tenant_count = Tenant::where('business_id',$value)->count();
                    if($tenant_count >= $sub->limit_tenant) {
                        $fail('Tenant limit reached '.$sub->limit_tenant);
                    }
                },
            ],
            'name' => 'required|string',
            'category' => 'required|string',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'rest_area_id' => 'required|integer',
            'time_start' => 'required|string',
            'time_end' => 'required|string',
            'phone' => 'nullable|string',
            'manager' => 'nullable|string',
            'merchant_id' => 'nullable|integer',
            'sub_merchant_id' => 'nullable|integer',
            'is_open' => 'required|boolean',
        ];
    }
}
