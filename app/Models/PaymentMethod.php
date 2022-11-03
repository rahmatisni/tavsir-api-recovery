<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethod extends BaseModel
{
    protected $table = 'ref_payment_method';

    protected $fillable = [
        'name',
        'code_name',
        'code_sof',
        'is_active',
        'fee',
    ];

    public function order()
    {
        return $this->hasMany(TransOrder::class, 'payment_method_id');
    }
}
