<?php

namespace App\Models;

use App\Models\BaseModel;

class TransSaldo extends BaseModel
{
    protected $table = 'trans_saldo';

    protected $fillable = [
        'rest_area_id',
        'tenant_id',
        'cashier_id',
        'saldo',
    ];

    public function scopeByCashier($query)
    {
        return $query->where('cashier_id', auth()->user()->id);
    }

    public function trans_invoice()
    {
        return $this->hasMany(TransInvoice::class,'trans_saldo_id');
    }
}
