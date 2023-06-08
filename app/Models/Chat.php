<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends BaseModel
{
    use HasFactory;

    protected $table = 'trans_chat';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'trans_order_id',
        'chat',
    ];

    public function trans_order()
    {
        return $this->belongsTo(TransOrder::class, 'trans_order_id');
    }

    public function setChatAttribute($value)
    {
        $this->attributes['chat'] = json_encode($value);
    }

    public function getChatAttribute($value)
    {
        return json_decode($value);
    }
}
