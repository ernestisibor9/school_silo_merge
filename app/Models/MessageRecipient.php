<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageRecipient extends Model
{
    use HasFactory;

    protected $table = 'message_recipients'; // important if unsure

    protected $fillable = [
        'message_id',
        'receiver_id',
        'receiver_type',
        'read_at'
    ];
}
