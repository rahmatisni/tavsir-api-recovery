<?php

namespace App\Models;

use App\Models\BaseModel;

class VoucherDetail extends BaseModel
{
    protected $table = 'ref_voucher_detail';

    protected $filable = [
        'voucher_id',
        'type',
        'trx_id',
        'trx_amount',
        'current_balance',
        'last_balance',
    ];

    public function voucher_detail()
    {
        return $this->belongsTo(Voucher::class, 'id');
    }
}
