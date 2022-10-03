<?php

namespace App\Models;
use App\Models\BaseModel;

class TransCashbox extends BaseModel
{
    protected $table = 'trans_cashbox';
    protected $casts = [
        'input_cashbox_date' => 'datetime:Y-m-d H:i:s',
        'update_cashbox_date' => 'datetime:Y-m-d H:i:s',
    ];
    protected $fillable = [
        'trans_operational_id',
        'cashbox',
        'cashbox_old',
        'input_cashbox_date',
        'update_cashbox_date',
        'pengeluaran_cashbox',
        'description',
        'rp_va_bri',
        'rp_dd_bri',
        'rp_va_mandiri',
        'rp_va_bni',
        'rp_tav_qr',
        'rp_link_aja',
    ];

    public function trans_operational()
    {
        return $this->belongsTo(TransOperational::class, 'trans_operational_id');
    }
}
