<?php

namespace App\Models;
use App\Models\BaseModel;

class Business extends BaseModel
{
    protected $table = 'ref_business';

    protected $fillable = [
        'name',
        'category',
        'address',
        'latitude',
        'longitude',
        'owner',
        'phone',
    ];
}
