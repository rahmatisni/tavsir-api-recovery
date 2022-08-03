<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Image;
class Product extends BaseModel
{
    protected $table = 'ref_product';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'sku',
        'name',
        'photo',
        'price',
        'is_active',
        'description'
    ];

    public function setPhotoAttribute($value)
    {
        $file = request()->file('photo');
        if(is_file($file)) {
            $imagebefore = $this->photo;
            $img = Image::make($file->getRealPath());
            $imgPath = 'images/product/'.Carbon::now()->format('Ymd').time().'.'.$file->getClientOriginalExtension();
            $img->resize(200, null, function ($constraint) {
                $constraint->aspectRatio();
            })->save(public_path($imgPath));
            $this->attributes['photo'] = $imgPath;
            if(file_exists($imagebefore)) {
                unlink($imagebefore);
            }
        }
    }
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class,'tenant_id');
    }

    public function customize()
    {
        return $this->belongsToMany(Customize::class, 'trans_product_customize', 'product_id', 'customize_id')->withPivot('must_choose');
    }

    public function scopeByTenant($query)
    {
        return $query->where('tenant_id', auth()->user()->tenant_id);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
