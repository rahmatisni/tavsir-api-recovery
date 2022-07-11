<?php

namespace App\Models;

use App\Models\BaseModel;

class Tenant extends BaseModel
{
    protected $table = 'ref_tenant';

    protected $fillable = [
        'business_id',
        'name',
        'category',
        'address',
        'latitude',
        'longitude',
        'rest_area_id',
        'time_start',
        'time_end',
        'phone',
        'manager',
        'photo_url',
        'merchant_id',
        'sub_merchant_id',
        'is_open',
        'created_by',
        'created_at',
        'updated_at'
    ];
}
