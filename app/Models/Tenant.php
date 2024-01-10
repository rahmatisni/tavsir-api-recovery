<?php

namespace App\Models;
use App\Models\BaseModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;


class Tenant extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ref_tenant';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'business_id',
        'supertenant_id',
        'ruas_id',
        'name',
        'category_tenant_id',
        'address',
        'latitude',
        'longitude',
        'rest_area_id',
        'time_start',
        'time_end',
        'phone',
        'email',
        'manager',
        'photo_url',
        'merchant_id',
        'sub_merchant_id',
        'is_open',
        'is_verified',
        'in_takengo',
        'in_selforder',
        'created_by',
        'created_at',
        'updated_at',
        'is_subscription',
        'is_print',
        'is_scan',
        'is_composite',
        'list_payment',
        'list_payment_bucket',
        'url_self_order',
        'logo',
        'additional_information',
        'instagram',
        'facebook',
        'website',
        'note',
        'is_supertenant'
    ];

    public function setPhotoUrlAttribute($value)
    {
        $file = request()->file('photo_url');
        if (is_file($file)) {
            $file = request()->file('photo_url')->store('images');
            $imagebefore = $this->photo_url;
            $this->attributes['photo_url'] = $file;
            if(file_exists($imagebefore)) {
                unlink($imagebefore);
            }
        }
    }

    public function setLogoAttribute($value)
    {
        $file = request()->file('logo');
        if (is_file($file)) {
            $file = request()->file('logo')->store('images');
            $imagebefore = $this->logo;
            $this->attributes['logo'] = $file;
            if(file_exists($imagebefore)) {
                unlink($imagebefore);
            }
        }

        if(request()->is_delete_logo){
            $imagebefore = $this->logo;
            $this->attributes['logo'] = null;
            if(file_exists($imagebefore)) {
                unlink($imagebefore);
            }
        }
    }

    public function product()
    {
        return $this->hasMany(Product::class, 'tenant_id');
    }

    public function rest_area()
    {
        return $this->belongsTo(RestArea::class, 'rest_area_id');
    }

    public function ruas()
    {
        return $this->belongsTo(Ruas::class, 'ruas_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function order()
    {
        return $this->hasMany(TransOrder::class, 'tenant_id');
    }

    public function getRatingAttribute()
    {
        return $this->order?->average('rating') ?? 0;
    }

    public function getTotalRatingAttribute()
    {
        return $this->order?->where('rating', '>', 0)->count() ?? 0;
    }

    public function scopeCategoryCount($query)
    {
        return $query->groupBy('category_tenant_id')->select('category_tenant_id as kategori', DB::raw('COUNT(*) as tenant'));
    }

    public function saldo()
    {
        return $this->hasOne(TransSaldo::class, 'tenant_id');
    }

    public function sharing()
    {
        return $this->hasOne(Sharing::class, 'tenant_id');
    }

    public function supertenant()
    {
        return $this->belongsTo(Supertenant::class, 'supertenant_id');
    }

    public function category_tenant()
    {
        return $this->belongsTo(CategoryTenant::class, 'category_tenant_id');
    }

    public function cashear()
    {
        return $this->hasMany(User::class, 'tenant_id')->where('role',User::CASHIER);
    }

    public function parent_supertenant()
    {
        return $this->belongsTo(Tenant::class, 'supertenant_id');
    }

    public function child_supertenant()
    {
        return $this->hasMany(Tenant::class, 'supertenant_id');
    }


    //Product
    public function category()
    {
        return $this->hasMany(Category::class, 'tenant_id');
    }

    public function scopeByOwner($query)
    {
        return $query->where('business_id', auth()->user()->business_id);
    }

    public function scopeByTenant($query)
    {
        return $query->where('tenant_id', auth()->user()->tenant_id);
    }

    public function scopeNotMemberSupertenant($query)
    {
        return $query->whereNull('supertenant_id');
    }
    public function scopeBusinessToBe($query)
    {
        if(auth()->user()->role === 'OWNER'){
            return $query->where('business_id', auth()->user()->business_id);
        }
        else {
            return $query->where('id', auth()->user()->tenant_id);
        }
    }
    
    public function scopeMemberSupertenant($query)
    {
        return $query->whereNotNull('supertenant_id');
    }

}
