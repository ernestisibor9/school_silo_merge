<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id', 'sender_type',
        'receiver_id', 'receiver_type',
        'subject', 'message',
        'attachment', 'parent_id'
    ];

    // Sender relationship
    public function sender()
    {
        return $this->morphTo(null, 'sender_type', 'sender_id');
    }

    // Receiver relationship
    public function receiver()
    {
        return $this->morphTo(null, 'receiver_type', 'receiver_id');
    }

    // Replies
    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_id');
    }
}
