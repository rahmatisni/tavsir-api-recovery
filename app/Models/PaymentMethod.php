<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethod extends BaseModel
{
    protected $table = 'ref_payment_method';
    protected $appends = ['logo_url'];

    protected $fillable = [
        'code_name',
        'is_snap',
        'sof_id',
        'code',
        'logo',
        'name',
        'description',
        'payment_method_id',
        'payment_method_code',
        'is_percent',
        'service_fee', 
        'travshop',
        'self_order',
        'tavsir',
        'minimum_amount',
    ];

    public function order()
    {
        return $this->hasMany(TransOrder::class, 'payment_method_id');
    }

    public function payment_method_rule()
    {
        return $this->hasMany(PaymentMethodRule::class, 'payment_method_id');
    }

    public function setLogoAttribute($value)
    {
        $file = request()->file('logo');
        if (is_file($file)) {
            $file = request()->file('logo')->store('images');
            if (file_exists($this->logo)) {
                unlink($this->logo);
            }
            $this->attributes['logo'] = $file;
        }
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset($this->logo) : null;
    }
}
