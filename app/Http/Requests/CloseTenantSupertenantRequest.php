<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseTenantSupertenantRequest extends FormRequest
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
        $rules['tenant_id'] = 'required';
        if(request()->tenant_id){
            if(request()->tenant_id != 'all'){
                $rules['tenant_id'] = 'exists:ref_tenant,id';
            }
        }

        return $rules;
    }
}
