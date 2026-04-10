<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'subject',
        'attachment',
        'parent_id'
    ];

    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }

    public function conversation()
    {
        return $this->belongsTo(MessageConversation::class);
    }
}
