<?php

namespace App\Models;

use App\Models\BaseModel;

class TransInvoiceDerek extends BaseModel
{
    public $incrementing = false;

    protected $table = 'trans_invoice_derek';
    public $timestamps = false;

    public const PAID = 'PAID';
    public const UNPAID = 'UNPAID';

    protected $fillable = [
        'id',
        'invoice_id',
        'cashier_id',
        'pay_petugas_id',
        'nominal',
        'claim_date',
        'paid_date',
        'kwitansi_id',
        'status',
    ];

    public function trans_saldo()
    {
        return $this->belongsTo(TransSaldo::class, 'trans_saldo_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function pay_station()
    {
        return $this->belongsTo(Paystation::class, 'pay_station_id');
    }

    public function pay_petugas()
    {
        return $this->belongsTo(User::class, 'pay_petugas_id');
    }
}
