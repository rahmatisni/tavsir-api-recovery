<?php

namespace App\Models;
use App\Models\BaseModel;

class TransOrder extends BaseModel
{
    protected $table = 'trans_order_detil';

    protected $filable = [
        'trans_order_id',
        'product_id',
        'variant_name',
        'variant_price',
        'addon_name',
        'addon_price',
        'qty',
        'discount',
    ];

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }
}
