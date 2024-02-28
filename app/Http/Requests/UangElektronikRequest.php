<?php

namespace App\Http\Requests;

use App\Models\TransOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class UangElektronikRequest extends FormRequest
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
            'customer_id' => 'required',
            'customer_name' => 'required',
            'customer_phone' => 'required',
            'phone' => [
                'required',
                function($a, $v, $f){
                    if($v){
                        $barier = TransOrder::where('order_id','LIKE','%'.$v.'%')
                        ->where('created_at', '>=', Carbon::today())
                        ->where('description','dual')
                        ->whereIn('status', array('DONE','READY', 'PAYMENT_SUCCESS'))
                        ->count();
                        if ($barier >= 3) {
                            $f("Maximum transaksi $v 3x");
                        }
                    }
                }
            ],
            'code' => 'required',
            'flag' => 'nullable',
            'pilihan_token' => 'nullable',
            'result_pln' => 'nullable|array',
        ];
    }
}
