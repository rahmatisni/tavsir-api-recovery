<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Image;

class Product extends BaseModel
{
    use SoftDeletes;
    protected $table = 'ref_product';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'photo',
        'price',
        'stock',
        'is_active',
        'description'
    ];

    public function setPhotoAttribute($value)
    {
        $file = request()->file('photo');
        if (is_file($file)) {
            $file = request()->file('photo')->store('images');
            //     $imagebefore = $this->photo;
            //     $img = Image::make($file->getRealPath());
            //     $imgPath = 'images/product/'.Carbon::now()->format('Ymd').time().'.'.$file->getClientOriginalExtension();
            //     dd(\file_exists('images/product'));
            //     $img->resize(200, null, function ($constraint) {
            //         $constraint->aspectRatio();
            //     })->save($imgPath);
            //     dd(\file_exists($imagebefore));
            if (file_exists($this->photo)) {
                unlink($this->photo);
            }
            $this->attributes['photo'] = $file;
        }
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function customize()
    {
        return $this->belongsToMany(Customize::class, 'trans_product_customize', 'product_id', 'customize_id')->withPivot('must_choose');
    }

    public function scopeByTenant($query)
    {
        return $query->where('tenant_id', auth()->user()->tenant_id)->orderByRaw('is_active = 0')->orderByRaw('stock = 0')->orderBy('name', 'asc')->get();
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function trans_stock()
    {
        return $this->hasMany(TransStock::class, 'product_id');
    }

    public function last_stock()
    {
        return $this->trans_stock();
    }

    public function scopeBySupertenant($query)
    {
        $tenant = Tenant::where('supertenant_id',auth()->user()->supertenant_id)->pluck('id');
        return $query->whereIn('tenant_id', $tenant);
    }
}
