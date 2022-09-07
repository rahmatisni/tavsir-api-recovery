<?php

namespace App\Models;

use App\Models\BaseModel;

class RestArea extends BaseModel
{
    protected $table = 'ref_rest_area';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'time_start',
        'time_end',
        'is_open',
        'ruas_id',
        'photo'
    ];

    public function ruas()
    {
        return $this->belongsTo(Ruas::class,'ruas_id');
    }

    public function tenant()
    {
        return $this->hasMany(Tenant::class, 'rest_area_id');
    }

    public function setPhotoAttribute($value)
    {
        $file = request()->file('photo');
        if(is_file($file)) {
            $file = request()->file('photo')->store('images');
        //     $imagebefore = $this->photo;
        //     $img = Image::make($file->getRealPath());
        //     $imgPath = 'images/product/'.Carbon::now()->format('Ymd').time().'.'.$file->getClientOriginalExtension();
        //     dd(\file_exists('images/product'));
        //     $img->resize(200, null, function ($constraint) {
        //         $constraint->aspectRatio();
        //     })->save($imgPath);
        //     dd(\file_exists($imagebefore));
            if(file_exists($this->photo)) {
                unlink($this->photo);
            }
            $this->attributes['photo'] = $file;
        }
    }
}
