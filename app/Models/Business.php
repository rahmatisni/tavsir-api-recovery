<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class Business extends BaseModel
{
    
    use SoftDeletes;
    protected $table = 'ref_business';

    protected $date = [
        'subscription_end'
    ];

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
