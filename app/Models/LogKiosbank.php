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
        return json_decode($value, true);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
        $this->attributes['inquiry'] = json_encode($value);
        $this->attributes['payment'] = json_encode($value);
        $this->attributes['status'] = json_encode($value);

    }

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }
}
