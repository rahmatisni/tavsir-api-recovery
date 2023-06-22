<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraPrice extends Model
{
    use HasFactory;

    public const AKTIF = 'AKTIF';
    public const NONAKTIF = 'NONAKTIF';

    protected $table = 'ref_extra_price';

    protected $fillable = [
        'tenant_id',
        'name',
        'percent',
        'status',
    ];

    public function scopeByTenant($q)
    {
        return $q->where('tenant_id', auth()->user()->tenant_id);
    }
}
