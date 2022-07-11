<?php

namespace App\Models;

use App\Models\BaseModel;

class Product extends BaseModel
{
    protected $table = 'ref_product';

    protected $fillable = [
        'tenant_id',
        'category',
        'name',
        'photo_url',
        'variant_id',
        'variant_name',
        'price',
        'is_active',
        'description'
    ];
}
