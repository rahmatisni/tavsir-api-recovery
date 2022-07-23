<?php

namespace App\Models;

class Variant extends BaseModel
{
    protected $table = "ref_variant";

    public $timestamps = false;

    protected $fillable = [
        'name',
        'product_id',
        "detil",
    ];

    public function getDetilAttribute($value)
    {
        return $value ? json_decode($value) : [];
    }

    public function setDetilAttribute($value)
    {
        $this->attributes['detil'] = $value ? json_encode($value) : [];
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

}
