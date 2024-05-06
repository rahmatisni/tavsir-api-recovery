<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogKiosbank extends Model
{
    protected $table = 'log_kiosbank';

    protected $fillable = [
        'trans_order_id',
        'data',
        'inquiry',
        'payment',
        'status'
    ];

    public function getDataAttribute($value)
    {
        return $value ? json_decode($value, true) : $value;
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    public function getInquiryAttribute($value)
    {
        return $value ? json_decode($value, true) : $value;
    }

    public function setInquiryAttribute($value)
    {
        $this->attributes['inquiry'] = json_encode($value);
    }

    public function getPaymentAttribute($value)
    {
        return $value ? json_decode($value, true) : $value;
    }

    public function setPaymentAttribute($value)
    {
        $this->attributes['payment'] = json_encode($value);
    }

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }
}
