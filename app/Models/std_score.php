<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class std_score extends Model
{
    protected $table = 'std_score'; 
    protected $fillable = [
        'stid','scr','sbj','schid','clsid','ssn', 'trm','aid'
    ];
}
