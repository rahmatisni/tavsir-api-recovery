<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customize extends Model
{
    use HasFactory;

    protected $table = 'ref_customize';
    protected $fillable = ['tenant_id', 'name', 'pilihan'];
    
    public function tenant()
    {
        return $this->belongsTo('App\Models\Tenant');
    }

    public function getPilihanAttribute($value)
    {
        return json_decode($value);
    }

    public function setPilihanAttribute($value)
    {
        $vv = array_map(function ($v, $k) {
            return [
                "id" => $k+1,
                "name" => $v["name"],
                "price" => $v["price"],
                "is_available" => $v["is_available"],

            ];
        }, $value, array_keys($value));
        $this->attributes['pilihan'] = json_encode($vv);
    }

    public function scopeByTenant($query)
    {
        return $query->where('tenant_id', auth()->user()->tenant_id);
    }
}
