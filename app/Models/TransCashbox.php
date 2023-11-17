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
        'rp_dd_mandiri',
        'rp_va_bni',
        'rp_tav_qr',
        'rp_link_aja',
        'rp_cash',
        'rp_total',
        'rp_edc',
        'sharing',

    ];

    public function trans_operational()
    {
        return $this->belongsTo(TransOperational::class, 'trans_operational_id');
    }

    public function getTotalDigitalAttribute($value)
    {
        return $this->rp_va_bri + $this->rp_dd_bri + $this->rp_va_mandiri +  $this->rp_dd_mandiri + $this->rp_va_bni + $this->rp_link_aja;
    }
}
