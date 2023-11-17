<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransProductRaw extends BaseModel
{
    use SoftDeletes;
    protected $table = 'trans_product_raw';

    protected $fillable = [
        'parent_id',
        'child_id',
        'qty',
    ];

    public function bahan_baku()
    {
        return $this->belongsTo(Product::class, 'child_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'parent_id', 'id');
    }
}
