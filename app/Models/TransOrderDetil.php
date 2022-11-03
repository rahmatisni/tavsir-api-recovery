<?php

namespace App\Models;

use App\Models\BaseModel;

class TransOrderDetil extends BaseModel
{
    protected $table = 'trans_order_detil';
    public $timestamps = false;
    protected $filable = [
        'trans_order_id',
        'product_id',
        'product_name',
        'customize',
        'price',
        'qty',
        'total_price',

    ];

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }

    public function getCustomizeAttribute($value)
    {
        return json_decode($value);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
