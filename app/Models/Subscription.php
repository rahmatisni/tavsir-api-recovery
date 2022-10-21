<?php

namespace App\Models;
use App\Models\BaseModel;

class Subscription extends BaseModel
{
    protected $table = 'trans_subscription';
    
    public const ACTIVE = 'ACTIVE';
    public const INACTIVE = 'INACTIVE';

    protected $fillable = [
        'business_id',
        'masa_aktif',
        'limit_tenant',
        'limit_cashier',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
