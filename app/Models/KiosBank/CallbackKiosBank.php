<?php

namespace App\Models\KiosBank;

use App\Models\BaseModel;

class CallbackKiosBank extends BaseModel
{
    protected $table = 'trans_order';

    protected $fillable = [
        'order_id',
        'status'
    ];
}
