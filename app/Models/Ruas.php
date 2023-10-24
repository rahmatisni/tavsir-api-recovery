<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;


class Ruas extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ref_ruas';

    protected $fillable = [
        'name'
    ];
}
