<?php

namespace App\Models;
use App\Models\BaseModel;

class TransOperational extends BaseModel
{
    protected $table = 'trans_operational';
    protected $casts = [
        'start_date' => 'datetime:Y-m-d H:i:s',
        'end_date' => 'datetime:Y-m-d H:i:s',
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
        return $this->hasOne(TransCashbox::class,'trans_operational_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'casheer_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopeByRole()
    {
        return $this->when(auth()->user()->role == User::TENANT, function($q){
                        $q->where('tenant_id', auth()->user()->tenant_id);
                    })
                    ->when(auth()->user()->role == User::CASHIER, function($q){
                        $q->where('casheer_id', auth()->user()->id);
                    });
    }
}
