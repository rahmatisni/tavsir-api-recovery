<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallbackLA extends Model
{
    protected $table = 'log_inform_la';

    protected $fillable = [
        'id',
        'trans_order_id',
        'data',
        'inquiry',
        'payment',
        'status',

    ];
}
