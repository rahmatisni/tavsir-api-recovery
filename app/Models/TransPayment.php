<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogInformLa;

class TransPayment extends Model
{
    protected $table = 'trans_payment';
    protected $casts = [
        'data' => 'array',
        'inquiry' => 'array',
        'payment' => 'array',


    ];
    protected $fillable = [
        'refnum', 'orderid_sof', 'tenant_kriteria','issuer_name'

    ];
    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }

    public function CallbackLA()
    {
        return $this->belongsTo(CallbackLA::class, 'trans_order_id');
    }
}
