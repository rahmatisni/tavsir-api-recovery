<?php

namespace App\Models;

use App\Models\BaseModel;

class TransDerek extends BaseModel
{
    protected $table = 'trans_order_derek';
    public $incrementing = false;

    protected $filable = [
        'id',
        'status',
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
        'customer_phone',
        'payment_method_id',
        'payment_id',
        'discount',
        'casheer_id',
        'pay_amount',
        'code_verif',
        'canceled_by',
        'canceled_name',
        'reason_cancel',
        'voucher_id',
        'id_ops',
        'saldo_qr',
        'created_at',
        'updated_at',
        'is_refund',
        'description',
        'harga_kios',
        'deleted_at',
        'addon_total',
        'margin',
        'net_margin',
        'sharing_code',
        'sharing_amount',
        'sharing_proportion',
        'id_derek',
        'order_id_derek',
        'user_id_derek',
        'user_name_derek',
        'user_lat_derek',
        'user_long_derek',
        'user_street_derek',
        'user_phone_derek',
        'forward_id_derek',
        'forward_name_derek',
        'hero_id_derek',
        'hero_name_derek',
        'hero_lat_derek',
        'hero_long_derek',
        'date_derek',
        'is_solve_derek',
        'solve_date_derek',
        'solve_end_date_derek',
        'solve_picture_derek',
        'solve_comment_derek',
        'kode_ruas_derek',
        'kode_cabang_derek',
        'biaya_awal_derek',
        'biaya_tambahan_derek',
        'biaya_perposid_derek',
        'total_biaya_derek',
        'tujuan_derek',
        'tujuan_lat_derek',
        'tujuan_long_derek',
        'tujuan_address_derek',
        'picture_derek',
        'kendala_derek',
        'jenis_kendaraan_derek',
        'plat_nomor_derek',
        'description_derek',
        'jarak_derek',
        'rating_derek',
        'rating_comment_derek',
        'ruas_state_derek',
        'jalur_state_derek',
        'created_at_derek',
        'updated_at_derek',
        'confirmed_at_derek',
        'canceled_at_derek',
        'paid_at_derek',
        'refund_at_derek',
        'arrived_at_derek',
        'refund_claimed_at_derek',
        'payment_metode_derek',
        'transaction_id_derek',
        'derek_detail_id_derek',
        'is_notif_derek',
        'invoice_id'
    ];

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function payment()
    {
        return $this->hasOne(TransPayment::class, 'trans_order_id');
    }

  

    public function invoice_derek()
    {
        return $this->hasOne(TransInvoiceDerek::class, 'id', 'invoice_id');
    }
    
    
}

