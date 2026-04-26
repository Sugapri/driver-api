<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'sender_type',
        'text',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
