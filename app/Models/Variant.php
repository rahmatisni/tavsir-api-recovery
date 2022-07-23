<?php

namespace App\Models;

class Variant extends BaseModel
{
    protected $table = "ref_variant";

    public $timestamps = false;

    protected $fillable = [
        'name',
        'product_id',
        "sub_variant",
    ];

    public function getSubVariantAttribute($value)
    {
        return $value ? json_decode($value) : [];
    }

    public function setSubVariantAttribute($value)
    {
        $vv = array_map(function ($v, $k) {
            return [
                "id" => $k+1,
                "name" => $v["name"],
                "price" => $v["price"],
            ];
        }, $value, array_keys($value));
        $this->attributes['sub_variant'] = $vv ? json_encode($vv) : [];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
