<?php

namespace App\Models;

use App\Models\BaseModel;

class TransSharing extends BaseModel
{
    protected $table = 'trans_sharing';

    protected $fillable = [
        'trans_order_id',
        'order_id',
        'order_type',
        'payment_method_id',
        'payment_method_name',
        'sub_total',

        'pengelola_id',
        'persentase_pengelola',
        'total_pengelola',

        'supertenant_id',
        'persentase_supertenant',
        'total_supertenant',

        'tenant_id',
        'persentase_tenant',
        'total_tenant',
    ];

    public function scopeByRole($query)
    {
        $user = auth()->user();
        $role = $user->role;
        if ($role == User::TENANT || $role == User::CASHIER) {
            $query->where('tenant_id', $user->tenant_id);
        }

        if ($role == User::SUPERTENANT) {
            $query->where('supertenant_id', $user->tenant_id);
        }

        return $query;
    }
}
