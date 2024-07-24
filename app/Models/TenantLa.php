<?php

namespace App\Models;
use App\Models\BaseModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;


class TenantLa extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ref_tenant_la';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id',
        'tenant_id',
        'partner_mid',
        'merchant_id',
        'merchant_pan',
        'merchant_name',
        'merchant_criteria',
        'postal_code',
        'city'
    ];
}