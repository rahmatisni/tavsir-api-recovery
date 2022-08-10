<?php

namespace App\Models;

use App\Models\BaseModel;

class Voucher extends BaseModel
{
    protected $table = 'ref_voucher';

    protected $fillable = [
        'voucher_uuid',
        'customer_id',
        'phone',
        //'trx_id',
        'balance',
        'qr',
        'auth_id',
        'paystation_id',
        'created_by',
        'created_at',
        'updated_at'
    ];


    public function setBalanceHistoryAttribute($value)
    {
        $this->attributes['balance_history'] = json_encode($value);
    }

    public function getBalanceHistoryAttribute($value)
    {
        return json_decode($value, true);
    }
}
