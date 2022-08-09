<?php

namespace App\Models;

use App\Models\BaseModel;

class Tenant extends BaseModel
{
    protected $table = 'ref_tenant';

    protected $fillable = [
        'business_id',
        'ruas_id',
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

    public function product()
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    public function category()
    {
        return $this->hasMany(Category::class, 'tenant_id');
    }

    public function rest_area()
    {
        return $this->belongsTo(RestArea::class, 'rest_area_id');
    }

    public function ruas()
    {
        return $this->belongsTo(Ruas::class, 'ruas_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
