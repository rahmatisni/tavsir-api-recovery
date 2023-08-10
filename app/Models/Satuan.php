<?php

namespace App\Models;

use App\Models\BaseModel;

class Satuan extends BaseModel
{
    protected $table = 'ref_satuan';

    public const berat = 'berat';
    public const volume = 'volume';
    public const unit = 'unit';

    public static function tipe()
    {
        return [Satuan::berat, Satuan::volume, Satuan::unit];
    }

    protected $fillable = [
        'type',
        'name'
    ];
}
