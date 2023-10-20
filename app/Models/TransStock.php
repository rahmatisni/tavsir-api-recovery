<?php

namespace App\Models;

use App\Models\BaseModel;

class TransStock extends BaseModel
{
    protected $table = 'trans_stock';

    public const INIT = 'init';
    public const IN = 'in';
    public const OUT = 'out';


    protected $fillable = [
        'product_id',
        'tenant_id',
        'current_stock',
        'recent_stock',
        'stock_type',
        'stock_amount',
        'description',
        'price_capital',
        'total_capital',
    ];

    protected $appends = ['lates_stock'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function scopeByTenant($query)
    {
        $query->whereHas('product', function ($q) {
            $q->byTenant()->withTrashed();
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLatesStockAttribute()
    {
        if ($this->stock_type == self::OUT) {
            return  max($this->recent_stock - $this->stock_amount, 0);
        }

        return max($this->recent_stock + $this->stock_amount, 0);
    }

    public function stockTypeLabel()
    {
        if ($this->stock_type == self::INIT) {
            return 'Stock Awal';
        } elseif ($this->stock_type == self::IN) {
            return 'Stock Masuk';
        } elseif ($this->stock_type == self::OUT) {
            return 'Stock Keluar';
        } else {
            return $this->stock_type;
        }
    }

    public function scopeMasuk($query)
    {
        return $query->whereIn('stock_type', [self::INIT, self::IN]);
    }

    public function scopeKeluar($query)
    {
        return $query->whereIn('stock_type', [self::OUT]);
    }
}
