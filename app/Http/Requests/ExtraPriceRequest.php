<?php

namespace App\Http\Requests;

use App\Models\ExtraPrice;
use Illuminate\Foundation\Http\FormRequest;

class ExtraPriceRequest extends FormRequest
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
            'name' => 'required|unique:ref_extra_price,name,'.request()->id.',id,tenant_id,'.auth()->user()->tenant_id,
            'is_percent' => 'required|boolean',
            'price' => 'required|numeric',
            'status' => 'required|in:'.ExtraPrice::AKTIF.','.ExtraPrice::NONAKTIF
        ];
        if(request()->is_percent == 1){
            $rule['price'] = 'required|numeric|min:0|max:100';
        }

        return $rule;
    }
}
