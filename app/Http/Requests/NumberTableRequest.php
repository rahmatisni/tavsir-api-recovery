<?php

namespace App\Http\Requests;

use App\Models\NumberTable;
use Illuminate\Foundation\Http\FormRequest;

class NumberTableRequest extends FormRequest
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
        $rule =  [
            'name' => 'required|unique:ref_number_table,name,'.request()->id.',id,tenant_id,'.auth()->user()->tenant_id,
        ];

        return $rule;
    }
}
