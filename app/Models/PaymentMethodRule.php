<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethodRule extends BaseModel
{
    protected $table = 'ref_payment_method_rule';

    protected $fillable = [
        'payment_method_id',
        'title',
        'body',
    ];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
