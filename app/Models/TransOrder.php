<?php

namespace App\Models;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TransOrder extends BaseModel
{
    protected $table = 'trans_order';

    protected $filable = [
        'sub_total',
        'fee',
        'total',
        'business_id',
        'tenant_id',
        'customer_id',
        'voucher_id',
        'payment_method_id',
        'payment_id',
        'discount',
    ];

    public function detil()
    {
        return $this->hasMany(TransOrder::class, 'trans_order_id');
    }

}
