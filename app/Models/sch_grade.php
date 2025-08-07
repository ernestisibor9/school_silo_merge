<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class sch_grade extends Model
{
    protected $table = 'sch_grade'; 
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','grd','g0','g1','schid','clsid','ssn', 'trm'
    ];
}
