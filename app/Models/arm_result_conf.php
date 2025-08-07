<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class arm_result_conf extends Model
{
    protected $table = 'arm_result_conf'; 
    protected $fillable = [
        'arm','stid','schid','clsid','sbid','ssn', 'trm','rmk','stat'
    ];
}
