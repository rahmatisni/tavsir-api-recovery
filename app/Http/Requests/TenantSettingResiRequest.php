<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;

class TenantSettingResiRequest extends FormRequest
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
            'is_delete_logo' => 'nullable|boolean',
            'logo' => 'nullable|max:5000|mimes:jpg,png,jpeg|image',
            'additional_information' => 'nullable|max:50',
            'instagram' => 'nullable|max:50',
            'facebook' => 'nullable|max:50',
            'website' => 'nullable|max:50',
            'note' => 'nullable|max:120'
        ];
    }
}
