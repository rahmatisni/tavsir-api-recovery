<?php

namespace App\Models\KiosBank;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class CallbackKiosBank extends BaseModel
{
 
    use HasFactory;

    protected $table = 'trans_order';

    protected $fillable = [
        'id',
        'order_id',
        'status'
    ];
}
