<?php

namespace App\Http\Requests\Pos;

use App\Models\Constanta\ProductType;
use Illuminate\Foundation\Http\FormRequest;

class CategoryProductRequest extends FormRequest
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
            'name' => 'required|string|max:20|unique:ref_category,name,'.($this->id ?? NULL).',id,tenant_id,'.auth()->user()->tenant_id.',type,'.ProductType::PRODUCT,
        ];
    }
}
