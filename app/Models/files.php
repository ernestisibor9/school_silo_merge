<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class files extends Model
{
    protected $table = 'files'; 
    protected $fillable = [
        'user_id','folder', 'trm', 'file'
    ];
}
