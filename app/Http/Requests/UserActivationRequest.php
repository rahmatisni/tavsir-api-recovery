<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserActivationRequest extends FormRequest
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
            'id' => [
                function ($attribute, $value, $fail) {
                    $user = User::where('id', $value)
                        ->where('role', User::CASHIER)
                        ->first();
                    if (!$user) {
                        $fail('User cashier not found');
                    }
                    $tenant = $user->tenant;
                    $subscription = Subscription::where('super_merchant_id', $tenant->business_id)
                        ->where('type', Subscription::OWNER)
                        ->first();
                    if (!$subscription) {
                        $fail('Does not have subscription');
                    }
                    if ($subscription->status != Subscription::ACTIVE) {
                        $fail('Subscription is not active');
                    }
                    $user_tenant_count = User::where('tenant_id', $value)
                        ->where('role', User::CASHIER)
                        ->where('status', User::ACTIVE)
                        ->count();
                    if ($user_tenant_count >= $subscription->limit_cashier) {
                        $fail('User limit reached');
                    }
                },
            ],
        ];
    }
}
