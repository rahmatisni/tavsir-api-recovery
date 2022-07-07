<?php

namespace App\Models;

use App\Models\Traits\RaidModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    use HasFactory, RaidModel;
}