<?php

namespace App\Models;

use App\Models\BaseModel;

class Bank extends BaseModel
{
    protected $table = 'ref_bank';

    protected $fillable = [
        'name',
    ];

    public function trans_edc()
    {
        return $this->hasMany(TransEdc::class);
    }
}
