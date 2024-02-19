<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\KiosBank\ProductKiosBank;
use App\Models\Traits\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
class TransOrder extends BaseModel
{
    use Uuid, SoftDeletes;
    
    protected $table = 'trans_order';
    public const CART = 'CART';
    public const PENDING = 'PENDING';
    public const WAITING_OPEN = 'WAITING_OPEN';
    public const WAITING_CONFIRMATION_TENANT = 'WAITING_CONFIRMATION_TENANT';
    public const WAITING_CONFIRMATION_USER = 'WAITING_CONFIRMATION_USER';
    public const WAITING_PAYMENT = 'WAITING_PAYMENT';
    public const PAYMENT_SUCCESS = 'PAYMENT_SUCCESS';
    public const PREPARED = 'PREPARED';
    public const READY = 'READY';
    public const DONE = 'DONE';
    public const CANCEL = 'CANCEL';
    public const REFUND = 'REFUND';
    public const QUEUE = 'QUEUE';


    public const ORDER_TAKE_N_GO = 'TAKE_N_GO';
    public const ORDER_SELF_ORDER = 'SELF_ORDER';

    public const POS = 'POS';
    public const ORDER_TRAVOY = 'ORDER_TRAVOY';

    public const ORDER_DEREK_ONLINE = 'DEREK_ORDER';


    public const DINE_IN = 'dine_in';
    public const TAKE_AWAY = 'take_away';

    public const CANCELED_BY_CASHEER = 'CASHEER';
    public const CANCELED_BY_CUSTOMER = 'CUSTOMER';


    protected $fillable = [
        'order_type',
        'cosume_type',
        'nomor_name',
        'sub_total',
        'sub_merchant_id',
        'fee',
        'total',
        'business_id',
        'tenant_id',
        'supertenant_id',
        'customer_id',
        'voucher_id',
        'payment_method_id',
        'payment_id',
        'discount',
        'is_save',
        'rating',
        'description',
        'sharing_code',
        'sharing_amount',
        'sharing_proportion',
        'invoice_id'
    ];

    public function trans_order_arsip()
    {
        return $this->hasMany(TransOrderArsip::class, 'trans_order_id');
    }

    public function addon_price()
    {
        return $this->hasMany(AddonPrice::class, 'trans_order_id');
    }

    public function detil()
    {
        return $this->hasMany(TransOrderDetil::class, 'trans_order_id');
    }

    public function product()
    {
        return $this->hasManyThrough(Product::class, TransOrderDetil::class);
    }

    public function rest_area()
    {
        return $this->belongsTo(RestArea::class, 'rest_area_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id')->withTrashed();
    }

    public function supertenant()
    {
        return $this->belongsTo(Supertenant::class, 'supertenant_id')->withTrashed();
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function payment()
    {
        return $this->hasOne(TransPayment::class, 'trans_order_id');
    }

    public function log_kiosbank()
    {
        return $this->hasOne(LogKiosbank::class, 'trans_order_id');
    }

    public function scopeFromTakengo($query)
    {
        return $query->where('order_type', self::ORDER_TAKE_N_GO);
    }

    public function scopeFromTavsir($query)
    {
        return $query->where('order_type', self::POS);
    }

    public function scopeFromTravoy($query)
    {
        return $query->where('order_type', self::ORDER_TRAVOY);
    }

    public function scopeDone($query)
    {
        return $query->whereIn('status', [self::DONE, self::REFUND]);
    }

    public function casheer()
    {
        return $this->belongsTo(User::class, 'casheer_id');
    }

    public function chat()
    {
        return $this->hasOne(Chat::class, 'trans_order_id');
    }

    public function scopePaymentMethodCount($query)
    {
        return $query->groupBy('payment_method_id')->select('payment_method_id as method', DB::raw('COUNT(*) as total'));
    }

    public function labelOrderType()
    {
        if ($this->order_type == self::ORDER_TAKE_N_GO) {
            return 'Take N Go';
        } elseif ($this->order_type == self::POS) {
            return 'POS';
        } else {
            return $this->order_type;
        }
    }

    public function scopeByRole($query)
    {
        return $query->when(auth()->user()->role == User::TENANT, function ($q) {
                $q->where('tenant_id', auth()->user()->tenant_id);
            })
            ->when(auth()->user()->role == User::CASHIER, function ($q) {
                $q->where('casheer_id', auth()->user()->id);
            })
            ->when(auth()->user()->role == User::SUPERTENANT, function ($q) {
                $q->where('supertenant_id', auth()->user()->supertenant_id);
            });;
    }

    public function statusLabel()
    {
                if ($this->status == 'READY') {
            return str_replace("_", " ", self::PAYMENT_SUCCESS);

        }
        elseif ($this->status == 'DONE') {
            return str_replace("_", " ", self::PAYMENT_SUCCESS);

        }
        else {
            return str_replace("_", " ", $this->status);
        }
    }

    public function consumeTypeLabel()
    {
        if ($this->consume_type == self::DINE_IN) {
            return 'Dine In';
        } elseif ($this->consume_type == self::TAKE_AWAY) {
            return 'Take Away';
        } else {
            return $this->consume_type;
        }
    }

    public function trans_edc()
    {
        return $this->hasOne(TransEdc::class, 'trans_order_id');
    }

    public function scopeBySupertenant($query)
    {
        $tenant = Tenant::where('supertenant_id',auth()->user()->supertenant_id)->pluck('id');
        return $query->whereIn('tenant_id', $tenant);
    }

    public function codeProductKiosbank()
    {
        if($this->order_type == self::ORDER_TRAVOY)
        {
            return explode('-',$this->order_id)[0] ?? '';
        }
        return null;
    }

    public function productKiosbank()
    {
        if($this->order_type == self::ORDER_TRAVOY)
        {
            $kode = explode('-',$this->order_id)[0] ?? '';
            return ProductKiosBank::where('kode', $kode)->first();
        }
        return null;
    }

    public function getMargin()
    {
        if($this->order_type == self::ORDER_TRAVOY)
        {
            return $this->sub_total - $this->harga_kios;   
        }
        return 0;
    }
}
