<?php

namespace App\Models;

use App\Models\BaseModel;

class Voucher extends BaseModel
{
    protected $table = 'ref_voucher';

    protected $fillable = [
        'nama_lengkap',
        'username',
        'voucher_uuid',
        'customer_id',
        'phone',
        'balance',
        'qr_code_use',
        'rest_area_id',
    ];


    public function setBalanceHistoryAttribute($value)
    {
        $this->attributes['balance_history'] = json_encode($value);
    }

    public function getBalanceHistoryAttribute($value)
    {
        return json_decode($value, true);
    }

    public function rest_area()
    {
        return $this->belongsTo(RestArea::class, 'rest_area_id');
    }
}
