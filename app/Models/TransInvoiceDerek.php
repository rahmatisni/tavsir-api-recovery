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
        'file'

    ];

    public function setFileAttribute($value)
    {
        $file = request()->file('file');
        if (is_file($file)) {
            $file = request()->file('file')->store('public'.request()->document_type);
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            $this->attributes['file'] = $file;
        }
    }

    public function trans_saldo()
    {
        return $this->belongsTo(TransSaldo::class, 'trans_saldo_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'pay_petugas_id');
    }

    public function pay_station()
    {
        return $this->belongsTo(Paystation::class, 'pay_station_id');
    }

}
