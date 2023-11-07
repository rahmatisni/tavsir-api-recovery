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
        'is_supertenant',
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
        'created_by',
        'created_at',
        'updated_at',
        'is_subscription',
        'is_print',
        'is_scan',
        'is_composite',
        'list_payment',
        'list_payment_bucket',
        'url_self_order'
    ];

    public function setPhotoUrlAttribute($value)
    {
        $file = request()->file('photo_url');
        if (is_file($file)) {
            $file = request()->file('photo_url')->store('images');
            //     $imagebefore = $this->photo;
            //     $img = Image::make($file->getRealPath());
            //     $imgPath = 'images/product/'.Carbon::now()->format('Ymd').time().'.'.$file->getClientOriginalExtension();
            //     dd(\file_exists('images/product'));
            //     $img->resize(200, null, function ($constraint) {
            //         $constraint->aspectRatio();
            //     })->save($imgPath);
            //     dd(\file_exists($imagebefore));
            $this->attributes['photo_url'] = $file;
            //     if(file_exists($imagebefore)) {
            //         unlink($imagebefore);
            //     }
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

}
