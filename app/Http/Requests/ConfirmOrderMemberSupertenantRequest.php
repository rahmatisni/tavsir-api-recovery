<?php

namespace App\Http\Requests;

use App\Models\TransOrderDetil;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmOrderMemberSupertenantRequest extends FormRequest
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
            'detil_id' => 'required|exists:trans_order_detil,id',
            'status' => 'required|in:'.TransOrderDetil::STATUS_CANCEL.','.TransOrderDetil::STATUS_READY
        ];
    }
}
