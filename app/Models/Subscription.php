<?php

namespace App\Models;

use App\Models\BaseModel;
use Carbon\Carbon;

class Subscription extends BaseModel
{
    protected $table = 'trans_subscription';
    public const AKTIF = 'AKTIF';
    public const TIDAK_AKTIF = 'TIDAK AKTIF';
    public const WAITING_ACTIVATION = 'WAITING AKTIVASI';

    public const PKS = 'PKS';
    public const BUKTI_BAYAR = 'BUKTI BAYAR';

    public const JMRB = 'JMRB';
    public const OWNER = 'OWNER';

    public const TERKONFIRMASI = 'TERKONFIRMASI';
    public const MENUNGGU_KONFIRMASI = 'MENUNGGU KONFIRMASI';

    protected $fillable = [
        'type',
        'super_merchant_id',
        'masa_aktif',
        'limit_cashier',
        'document_type',
        'file',
    ];

    protected $date = [
        'start_date'
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
            $file = request()->file('file')->store('public/'.request()->document_type);
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            $this->attributes['file'] = $file;
        }
    }

    public function getEndDateAttribute()
    {
        return $this->start_date ? Carbon::parse($this->start_date)->addMonths($this->masa_aktif) : null;
    }

    public function getRemainingAttribute()
    {
        $remaining = $this->end_date ? now()->diffInDays($this->end_date, false) : 0;
        return $remaining < 0 ? 0 : $remaining;
    }

    public function getStatusAktivasiAttribute()
    {
        if($this->end_date){
            if ($this->remaining > 0) {
                return self::AKTIF;
            } else {
                return self::TIDAK_AKTIF;
            }
        }else{
            return self::WAITING_ACTIVATION;
        }
    }


    public function scopeByOwner($query, $business_id = null)
    {
        return $query->where('super_merchant_id', $business_id ?? auth()->user()->business_id);
    }

    public function scopelimitTenantCount($query)
    {
        return $query->where('status_aktivasi', Subscription::AKTIF)->sum('limit_tenant');
    }

    public function scopelimitCashierCount($query)
    {
        return $query->where('status_aktivasi', Subscription::AKTIF)->sum('limit_cashier');
    }
}
