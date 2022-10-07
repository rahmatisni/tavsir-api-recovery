<?php

namespace App\Models;
use App\Models\BaseModel;

class TransOperational extends BaseModel
{
    protected $table = 'trans_operational';
    protected $casts = [
        'start_date' => 'datetime:Y-m-d H:i:s',
    ];
    protected $fillable = [
        'tenant_id',
        'periode',
        'casheer_id',
        'start_date',
        'end_date',
        'duration',
    ];

    public function trans_cashbox()
    {
        return $this->hasOne(TransCashbox::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'casheer_id');
    }
}
