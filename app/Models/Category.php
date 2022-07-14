<?php

namespace App\Models;

use App\Models\BaseModel;

class Category extends BaseModel
{
    protected $table = 'ref_category';

    protected $fillable = [
        'tenant_id',
        'name'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class,'tenant_id');
    }
}
