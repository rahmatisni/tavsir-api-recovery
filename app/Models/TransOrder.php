<?php

namespace App\Models;
use App\Models\BaseModel;
use App\Models\Traits\Uuid;

class TransOrder extends BaseModel
{
    use Uuid;

    protected $table = 'trans_order';
    public const PENDING = 'PENDING';
    public const PAYMENT = 'PAYMENT';
    public const WAITING_CONFIRMATION = 'WAITING_CONFIRMATION';
    public const WAITING_PAYMENT = 'WAITING_PAYMENT';
    public const PREPARED = 'PREPARED';
    public const READY = 'READY';
    public const DONE = 'DONE';
    public const CANCEL = 'CANCEL';

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
        return $this->hasMany(TransOrderDetil::class, 'trans_order_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

}
