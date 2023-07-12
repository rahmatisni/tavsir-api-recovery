<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Uuid;

class NumberTable extends Model
{
    use Uuid, HasFactory;

    protected $table = 'ref_number_table';

    protected $fillable = [
        'tenant_id',
        'name',
    ];

    public function scopeByTenant($q, $tenant_id = null)
    {
        $id = $tenant_id ?? auth()->user()->tenant_id;
        return $q->where('tenant_id', $id);
    }
}
