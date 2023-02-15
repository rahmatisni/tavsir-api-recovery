<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethod extends BaseModel
{
    protected $table = 'ref_payment_method';

    protected $fillable = [
        'code_name',
        'sof_id',
        'code',
        'name',
        'description',
        'payment_method_id',
        'payment_method_code',
    ];

    public function order()
    {
        return $this->hasMany(TransOrder::class, 'payment_method_id');
    }
}
