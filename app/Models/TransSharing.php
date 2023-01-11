<?php

namespace App\Models;

use App\Models\BaseModel;

class TransSharing extends BaseModel
{
    protected $table = 'trans_sharing';

    protected $fillable = [
        'trans_order_id',
        'sub_total',

        'pengelola_id',
        'persentase_pengelola',
        'total_pengelola',

        'supertenant_id',
        'persentase_supertenant',
        'total_supertenant',

        'tenant_id',
        'persentase_tenant',
        'total_tenant',
    ];
}
