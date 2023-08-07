<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BindRequest extends FormRequest
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
    public function rules(Request $request)
    {
        return [
            'customer_id' => 'required',
            'sof_code' => 'required',
            'customer_name' => 'required',
            'card_no' => ['required',Rule::unique('ref_bind')->where(function ($query) use ($request) {
                return $query->where('customer_id', $request->customer_id);
            })],
            'phone' => 'required',
            'email' => 'required',
            'exp_date' => 'required'
        ];
    }
}
