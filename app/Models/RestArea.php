<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestArea extends Model
{
    use HasFactory;

    protected $table = 'ref_rest_area';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'time_start',
        'time_end',
        'is_open'
    ];
}
