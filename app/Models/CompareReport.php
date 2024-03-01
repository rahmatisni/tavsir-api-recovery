<?php

namespace App\Models;

use App\Models\BaseModel;

class CompareReport extends BaseModel
{
    protected $table = 'vReportCompareGetPay';

    // protected $fillable = [
    //     'nama_lengkap',
    //     'username',
    //     'voucher_uuid',
    //     'customer_id',
    //     'phone',
    //     'balance',
    //     'qr_code_use',
    //     'rest_area_id',
    // ];

    public function detilDerek()
    {
        return $this->hasOne(TransDerek::class, 'transaction_id_derek', 'trans_order_id');
    }


}


