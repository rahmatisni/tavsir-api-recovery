<?php

namespace App\Models;

use App\Models\BaseModel;

class TransEdc extends BaseModel
{
    protected $table = 'trans_edc';

    protected $fillable = [
        'trans_order_id',
        'bank_id',
        'card_nomor',
        'ref_nomor',
    ];

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class,'trans_order_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class,'bank_id');
    }
}
