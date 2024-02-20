<?php

namespace App\Http\Requests;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantTerpaduRequest extends FormRequest
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
            'tenant_id' => ['required','array'],
            'tenant_id.*' => [
                'required',
                'integer',
                Rule::exists('ref_tenant','id')->whereNull('supertenant_id')->where('is_supertenant', 0)
            ]
        ];
    }
}
