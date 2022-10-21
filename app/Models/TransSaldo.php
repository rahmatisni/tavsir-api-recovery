<?php

namespace App\Models;

use App\Models\BaseModel;

class TransSaldo extends BaseModel
{
    protected $table = 'trans_saldo';

    protected $fillable = [
        'rest_area_id',
        'tenant_id',
        'saldo',
    ];

    public function scopeByRole($query)
    {
        $role = auth()->user()->role;
        if($role == User::TENANT || $role == User::CASHIER){
            return $query->where('tenant_id', auth()->user()->tenant_id);
        }else{
            return $query;
        }
    }

    public function scopeByTenant($query)
    {
        return $query->where('tenant_id', auth()->user()->tenant_id);
    }

    public function trans_invoice()
    {
        return $this->hasMany(TransInvoice::class,'trans_saldo_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function rest_area()
    {
        return $this->belongsTo(RestArea::class, 'rest_area_id');
    }
}
