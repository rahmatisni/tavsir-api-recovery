<?php

namespace App\Models;

use App\Models\BaseModel;

class NumberSave extends BaseModel
{
    protected $table = 'ref_number_save';

    protected $fillable = [
        'type',
        'customer_id',
        'number',
        'label',
    ];
}
