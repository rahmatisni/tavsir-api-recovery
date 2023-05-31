<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\Traits\Uuid;
use Illuminate\Support\Facades\DB;

class TransOrderArsip extends BaseModel
{
    protected $table = 'trans_order_arsip';
   
    protected $fillable = [
        'trans_order_id',
        'order_id',
        'order_type',
        'consume_type',
        'nomor_name',
        'sub_total',
        'fee',
        'service_fee',
        'total',
        'pickup_date',
        'confirm_date',
        'rating',
        'rating_comment',
        'business_id',
        'rest_area_id',
        'tenant_id',
        'supertenant_id',
        'merchant_id',
        'sub_merchant_id',
        'paystation_id',
        'customer_id',
        'customer_name',
        'payment_method_id',
        'payment_id',
        'discount',
        'casheer_id',
        'pay_amount',
        'code_verif',
        'status',
        'is_refund',
        'canceled_by',
        'canceled_name',
        'reason_cancel',
        'voucher_id',
        'id_ops',
        'saldo_qr',
        'description',
        'harga_kios',
        'settlement_at',
    ];
   
    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }
}
