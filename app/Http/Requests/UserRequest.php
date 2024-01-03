<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            // 'password' => 'required|string|min:6',
            'role' => 'required|string|in:' . User::ADMIN . ',' . User::USER . ',' . User::PAYSTATION . ',' . User::JMRB . ',' . User::TENANT . ',' . User::CASHIER . ',' . User::OWNER . ',' . User::JMRBAREA . ',' . User::AREA . ',' . User::SUPERTENANT . ',' . User::SUPERADMIN,
        ];
    }
}
