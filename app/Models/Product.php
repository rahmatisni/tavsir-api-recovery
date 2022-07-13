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
        'category',
        'sku',
        'name',
        'photo',
        'variant',
        'addon',
        'price',
        'is_active',
        'description'
    ];

    public function getVariantAttribute($value)
    {
        return json_decode($value);
    }

    public function setVariantAttribute($value)
    {
        $this->attributes['variant'] = json_encode($value);
    }

    public function getAddonAttribute($value)
    {
        return json_decode($value);
    }

    public function setAddonAttribute($value)
    {
        $this->attributes['addon'] = json_encode($value);
    }

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
}
