<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class msgthread extends Model
{
    protected $table = 'msgthread'; 
    
    protected $fillable = [
        'from', 'from_uid', 'to', 'to_uid', 'last_msg', 'subject', 'from_mail', 'to_mail'
    ];
}
