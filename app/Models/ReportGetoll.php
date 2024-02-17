<?php

namespace App\Models;

use App\Models\BaseModel;

class ReportGetoll extends BaseModel
{
    protected $table = 'report_getol';

    protected $fillable = [
        'Tanggal_Transaksi',
        'Merchant',
        'Sub_Merchant',
        'Ref_Number',
        'Kode_Metode_Pembayaran',
        'Source_of_Fund',
        'Nominal',
        'Status_Transaksi',
        'Tanggal_Settle',
        'PG_Fee',
        'Merchant_Fee',
        'Sub_Merchant_Fee',
        'SOF_Fee',
        'Status_Rekon',
        'Nomor_Rekon',
        'Tanggal_Rekon',
        'Remark_Disbursement',
        'Remark_Transaksi'
    ];

    // public function voucher_detail()
    // {
    //     return $this->belongsTo(Voucher::class, 'id');
    // }
}
