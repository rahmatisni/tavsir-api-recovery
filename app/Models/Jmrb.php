<?php

namespace App\Models;
use App\Models\BaseModel;

class Jmrb extends BaseModel
{
    protected $table = 'ref_jmrb';

    protected $fillable = [
        'pic',
        'email',
        'phone',
        'hp',
    ];
}
