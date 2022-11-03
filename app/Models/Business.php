<?php

namespace App\Models;

use App\Models\BaseModel;

class Business extends BaseModel
{
    protected $table = 'ref_business';

    protected $fillable = [
        'name',
        'email',
        'category',
        'address',
        'status_perusahaan',
        'latitude',
        'longitude',
        'owner',
        'phone',
    ];

    public function subscription()
    {
        return $this->hasMany(Subscription::class);
    }

    public function tenant()
    {
        return $this->hasMany(Tenant::class);
    }
}
