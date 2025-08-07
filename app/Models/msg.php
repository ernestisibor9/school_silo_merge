<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class msg extends Model
{
    protected $table = 'msg'; 
    protected $fillable = [
        'tid','body', 'who', 'art'
    ];
}
