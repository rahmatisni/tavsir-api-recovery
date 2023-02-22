<?php

namespace App\Models\KiosBank;

use App\Models\BaseModel;

class ProductKiosBank extends BaseModel
{
    protected $table = 'ref_product_kios_bank';

    protected $fillable = [
        'kategori',
        'sub_kategori',
        'kode',
        'name',
        'prefix_id'
    ];
}
