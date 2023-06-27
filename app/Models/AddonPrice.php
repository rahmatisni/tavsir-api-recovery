<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddonPrice extends Model
{
    protected $table = 'trans_addon_fee';

    protected $fillable = [
        'trans_order_id',
        'name',
        'price',
    ];
}
