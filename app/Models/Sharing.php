<?php

namespace App\Models;

use App\Models\BaseModel;

class Sharing extends BaseModel
{
    protected $table = 'ref_sharing';

    protected $fillable = [
        'nama_pks',
        'nomor_pks',
        'pengelola_id',
        'tenant_id',
        'supertenant_id',
        'sharing_code',
        'sharing_config',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'file',
    ];

    // public function setFileAttribute($value)
    // {
    //     $file = request()->file('file');
    //     if (is_file($file)) {
    //         $file = request()->file('file')->store('pks');
    //         if (file_exists($this->file)) {
    //             unlink($this->file);
    //         }
    //         $this->attributes['file'] = $file;
    //     }
    // }

    public function setFileAttribute($value)
    {
        $file = request()->file('file');
        if (is_file($file)) {
            $file = request()->file('file')->store('public/'.request()->document_type);
            if (file_exists($this->file)) {
                unlink($this->file);
            }
            $this->attributes['file'] = $file;
        }
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }


    public function supertenant()
    {
        return $this->belongsTo(Supertenant::class, 'supertenant_id');
    }

    public function scopeStatusActive($query)
    {
        return $query->where('status',1);
    }
}
