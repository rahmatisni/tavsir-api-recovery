<?php

namespace App\Models\KiosBank;

use App\Models\BaseModel;

class CallbackKiosBank extends BaseModel
{
    protected $table = 'trans_order';

    protected $fillable = [
        'id',
        'order_id',
        'status'
    ];
}
