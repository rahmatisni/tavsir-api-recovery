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
        'persentase_pengelola',
        'persentase_supertenant',
        'persentase_tenant',
        'waktu_mulai',
        'waktu_selesai',
        'status',
        'file',
    ];

    public function setFileAttribute($value)
    {
        $file = request()->file('file');
        if (is_file($file)) {
            $file = request()->file('file')->store('images');
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
}
