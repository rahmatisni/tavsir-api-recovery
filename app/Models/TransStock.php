<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class TransStock extends BaseModel
{
    protected $table = 'trans_stock';

    public const INIT = 'init';
    public const IN = 'in';
    public const OUT = 'out';


    protected $fillable = [
        'product_id',
        'current_stock',
        'stock_amount',
        'description'
    ];

    protected $appends = ['lates_stock'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeByTenant($query)
    {
        $query->whereHas('product', function ($q) {
            $q->byTenant();
        }, 1);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getLatesStockAttribute()
    {
        if ($this->stock_type == self::OUT) {
            return  $this->recent_stock - $this->stock_amount;
        }

        return $this->recent_stock + $this->stock_amount;
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
        return $query->whereIn('stock_type', [self::INIT, self::OUT]);
    }
}
