<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeStatusOrderReqeust extends FormRequest
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
            'status' => 'required|string|in:WAITING_OPEN,WAITING_CONFIRMATION_USER,WAITING_CONFIRMATION_TENANT,WAITING_PAYMENT,PAYMENT_SUCCESS,PREPARED,READY,DONE,CANCEL'
        ];
    }
}
