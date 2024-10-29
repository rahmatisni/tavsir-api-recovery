<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bind extends BaseModel
{
    use HasFactory;
    
    protected $table = 'ref_bind';

    protected $appends = ['is_valid'];

    protected $fillable = [
        'customer_id',
        'sof_code',
        'bind_id',
        'customer_name',
        'card_no',
        'phone',
        'email',
        'refnum',
        'exp_date',
        'token',
        'payment_method_id',
    ];

    public function getIsValidAttribute()
    {
        return $this->bind_id ? true : false;
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function getIsSnapAttribute()
    {
        return $this->payment_method?->is_snap ?? false;
    }
}
