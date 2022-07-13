<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        $request = request();
        if(is_file($request->file('photo'))) {
            $imagebefore = $this->photo;
            $this->attributes['photo'] = $request->file('photo')->store('images/product');
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
