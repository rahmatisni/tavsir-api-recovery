<?php

namespace App\Models;

use App\Models\BaseModel;

class Ruas extends BaseModel
{
    protected $table = 'ref_ruas';

    protected $fillable = [
        'name'
    ];
}
