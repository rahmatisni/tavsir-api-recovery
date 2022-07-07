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
        'is_open'
    ];
}
