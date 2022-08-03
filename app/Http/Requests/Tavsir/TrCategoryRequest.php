<?php

namespace App\Http\Requests\Tavsir;

use Illuminate\Foundation\Http\FormRequest;

class TrCategoryRequest extends FormRequest
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
            'name' => 'required|string|max:50|unique:ref_category,name,'.$this->id.',id,tenant_id,'.auth()->user()->tenant_id,
        ];
    }
}
