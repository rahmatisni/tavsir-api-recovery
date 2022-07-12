<?php

namespace App\Models;
use App\Models\BaseModel;

class Paystation extends BaseModel
{
    protected $table = 'ref_paystation';
    
    protected $fillable = [
        'name',
        'rest_area_id',
    ];
}
