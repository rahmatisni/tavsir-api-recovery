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
        'is_percent',
        'price',
        'status',
    ];

    public function scopeByTenant($q, $tenant_id = null)
    {
        $id = $tenant_id ?? auth()->user()->tenant_id;
        return $q->where('tenant_id', $id);
    }

    public function scopeAktif($q)
    {
        return $q->where('status', self::AKTIF);
    }
}
