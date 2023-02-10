<?php

namespace App\Models;

use App\Models\BaseModel;

class TransOrderDetil extends BaseModel
{
    public const STATUS_WAITING = 'WAITING';
    public const STATUS_READY = 'READY';
    public const STATUS_CANCEL = 'CANCEL';
    public const STATUS_DONE = 'DONE';

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
        'status',
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
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function isRefund()
    {
        return $this->status == TransOrderDetil::STATUS_CANCEL || $this->status == '';
    }

    public function priceRefund()
    {
        return $this->isRefund() ? (0 - $this->price) : $this->price; 
    }

    public function basePriceRefund()
    {
        return $this->isRefund() ? (0 - $this->base_price) : $this->base_price; 
    }

    public function totalPriceRefund()
    {
        return $this->isRefund() ? (0 - $this->total_price) : $this->total_price; 
    }
}
