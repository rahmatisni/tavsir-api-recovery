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

    use SoftDeletes;
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
}
