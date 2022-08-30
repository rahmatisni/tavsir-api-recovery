<?php

namespace App\Models;

use App\Models\BaseModel;

class RestArea extends BaseModel
{
    protected $table = 'ref_rest_area';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'time_start',
        'time_end',
        'is_open',
        'ruas_id'
    ];

    public function ruas()
    {
        return $this->belongsTo(Ruas::class,'ruas_id');
    }

    public function tenant()
    {
        return $this->hasMany(Tenant::class, 'rest_area_id');
    }
}
