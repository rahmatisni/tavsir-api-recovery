<?php

namespace App\Models;

use App\Models\BaseModel;

class TransInvoice extends BaseModel
{
    protected $table = 'trans_invoice';
    public $timestamps = false;

    public const PAID = 'PAID';
    public const UNPAID = 'UNPAID';

    protected $fillable = [];

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
}
