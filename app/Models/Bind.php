<?php

namespace App\Models;

use App\Models\BaseModel;

class Bind extends BaseModel
{
    protected $table = 'ref_bind';

    protected $appends = ['is_valid'];

    protected $fillable = [
        'customer_id',
        'sof_code',
        'customer_name',
        'card_no',
        'phone',
        'email',
        'refnum',
        'exp_date',
    ];

    public function getIsValidAttribute()
    {
        return $this->bind_id ? true : false;
    }
}
