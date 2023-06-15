<?php

namespace App\Models;

use App\Models\BaseModel;

class PriceSubscription extends BaseModel
{
    protected $table = 'ref_price_subscription';

    protected $fillable = [
        'price_tenant',
        'price_cashear',
    ];
}
