<?php

namespace App\Models;

use App\Models\BaseModel;

class VCompare extends BaseModel
{
    protected $table = 'vReportCompareGetPay';

    protected $fillable = [
    ];

    public function detilReport()
    {
        return $this->hasOne(ReportGetoll::class, 'Ref_Number', 'refnum');
    }
}


