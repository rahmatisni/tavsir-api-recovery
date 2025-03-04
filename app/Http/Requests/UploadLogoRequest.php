<?php

namespace App\Http\Requests;

use App\Services\Master\UploadLogoServices;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadLogoRequest extends FormRequest
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
            'kategori' => ['required','string',Rule::in(UploadLogoServices::kategoriArray())],
            'logo' => 'required|mimes:png|max:5000'
        ];
    }

    public function messages(): array
    {
        return [
            'kategori.in' => ':attribute must in value '.implode(',',UploadLogoServices::kategoriArray()),
        ];
    }
}
