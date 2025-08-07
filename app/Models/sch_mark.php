<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sch_mark extends Model
{
    protected $table = 'sch_mark'; 
    protected $fillable = [
        'name','ise','pt','schid','clsid','ssn', 'trm'
    ];
}
