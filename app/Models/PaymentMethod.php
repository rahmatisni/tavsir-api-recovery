<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethod extends BaseModel
{
    protected $table = 'ref_payment_method';
    
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];
}
