<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class LogJatelindo extends BaseModel
{
    public const inquiry = "inquiry";
    public const purchase = "purchase";
    public const advice = "advice";
    public const repeat = "repeat";

    protected $table = 'log_jatelindo';

    protected $fillable = [
        'trans_order_id',
        'type',
        'request',
        'response',
    ];

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id')->withTrashed();
    }

    public function getRequestAttribute($value)
    {
        return $value ? json_decode($value, true) : $value;
    }

    public function setRequestAttribute($value)
    {
        $this->attributes['request'] = json_encode($value);
    }

    public function getResponseAttribute($value)
    {
        return $value ? json_decode($value, true) : $value;
    }

    public function setResponseAttribute($value)
    {
        $this->attributes['response'] = json_encode($value);
    }
}
