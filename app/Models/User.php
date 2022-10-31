<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ADMIN = 'ADMIN';
    public const USER = 'USER';
    public const PAYSTATION = 'PAYSTATION';
    public const JMRB = 'JMRB';
    public const TENANT = 'TENANT';
    public const CASHIER = 'CASHIER';

    public const WAITING_APPROVE = 'WAITING_APPROVE';
    public const REJECT = 'REJECT';
    public const APPROVED = 'APPROVED';

    public const ACTIVE = 'ACTIVE';
    public const INACTIVE = 'INACTIVE';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'business_id',
        'merchant_id',
        'sub_merchant_id',
        'tenant_id',
        'rest_area_id',
        'paystation_id',
        'status',
        'fcm_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getIsAdminAttribute()
    {
        return $this->role === self::ADMIN;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    
    public function paystation()
    {
        return $this->belongsTo(Paystation::class, 'paystation_id');
    }
}
