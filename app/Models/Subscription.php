<?php

namespace App\Models;

use App\Models\BaseModel;

class Subscription extends BaseModel
{
    protected $table = 'trans_subscription';
    public const ACTIVE = 'ACTIVE';
    public const INACTIVE = 'INACTIVE';

    public const JMRB = 'JMRB';
    public const OWNER = 'OWNER';

    protected $fillable = [
        'type',
        'super_merchant_id',
        'masa_aktif',
        'limit_cashier',
        'file',
    ];

    public function superMerchant()
    {
        if ($this->type == self::JMRB) {
            return $this->belongsTo(Jmrb::class, 'super_merchant_id');
        } else {
            return $this->belongsTo(Business::class, 'super_merchant_id');
        }
    }

    public function setFileAttribute($value)
    {
        $file = request()->file('file');
        if (is_file($file)) {
            $file = request()->file('file')->store('images');
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            $this->attributes['file'] = $file;
        }
    }

    public function getEndDateAttribute()
    {
        return $this->created_at->addMonths($this->masa_aktif);
    }

    public function getRemainingAttribute()
    {
        $remaining = now()->diffInDays($this->created_at->addMonths($this->masa_aktif), false);
        return $remaining < 0 ? 0 : $remaining;
    }

    public function getStatusAttribute()
    {
        if ($this->remaining > 0) {
            return self::ACTIVE;
        } else {
            return self::INACTIVE;
        }
    }
}
