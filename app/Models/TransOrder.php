<?php

namespace App\Models;
use App\Models\BaseModel;
use App\Models\Traits\Uuid;
use Illuminate\Support\Facades\DB;

class TransOrder extends BaseModel
{
    use Uuid;

    protected $table = 'trans_order';
    public const CART = 'CART';
    public const PENDING = 'PENDING';
    public const WAITING_OPEN = 'WAITING_OPEN';
    public const WAITING_CONFIRMATION_TENANT = 'WAITING_CONFIRMATION_TENANT';
    public const WAITING_CONFIRMATION_USER = 'WAITING_CONFIRMATION_USER';
    public const WAITING_PAYMENT = 'WAITING_PAYMENT';
    public const PAYMENT_SUCCESS = 'PAYMENT_SUCCESS';
    public const PREPARED = 'PREPARED';
    public const READY = 'READY';
    public const DONE = 'DONE';
    public const CANCEL = 'CANCEL';

    public const ORDER_TAKE_N_GO = 'TAKE_N_GO';
    public const ORDER_TAVSIR = 'ORDER_TAVSIR';

    public const CANCELED_BY_CASHEER = 'CASHEER';
    public const CANCELED_BY_CUSTOMER = 'CUSTOMER';


    protected $fillable = [
        'order_type',
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
        'is_save',
        'rating',
    ];

    public function detil()
    {
        return $this->hasMany(TransOrderDetil::class, 'trans_order_id');
    }

    public function rest_area()
    {
        return $this->belongsTo(RestArea::class, 'rest_area_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function payment()
    {
        return $this->hasOne(TransPayment::class, 'trans_order_id');
    }

    public function scopeFromTakengo($query)
    {
        return $query->where('order_type',self::ORDER_TAKE_N_GO);
    }

    public function scopeFromTavsir($query)
    {
        return $query->where('order_type',self::ORDER_TAVSIR);
    }

    public function scopeDone($query)
    {
        return $query->where('status',self::DONE);
    }

    public function casheer()
    {
        return $this->belongsTo(User::class, 'casheer_id');
    }

    public function chat()
    {
        return $this->hasOne(Chat::class, 'trans_order_id');
    }

    public function scopePaymentMethodCount($query)
    {
        return $query->groupBy('payment_method_id')->select('payment_method_id as method', DB::raw('COUNT(*) as total'));
    }

    public function labelOrderType()
    {
        if($this->order_type == self::ORDER_TAKE_N_GO){
            return 'Take N Go';
        }else if($this->order_type == self::ORDER_TAVSIR){
            return 'Tavsir';
        }else{
            return $this->order_type;
        }
    }

    // public function scopeRestAreaCount($query)
    // {
    //     return $query->tenant()->groupBy('rest_area_id')->select('rest_area_id as area', DB::raw('COUNT(*) as total'));
    // }
}
