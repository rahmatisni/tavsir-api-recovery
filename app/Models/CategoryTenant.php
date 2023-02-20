<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoryTenant extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ref_category_tenant';
    protected $dates = ['deleted_at'];
    
    protected $fillable = [
        'name',
    ];

    public function tenant()
    {
        return $this->hasMany(Tenant::class);
    }

    public function supertenant()
    {
        return $this->hasMany(Supertenant::class);
    }
}
