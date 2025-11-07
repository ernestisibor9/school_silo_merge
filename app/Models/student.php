<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student extends Model
{
    protected $table = 'student';
    protected $primaryKey = 'sid';
    public $incrementing = false;
    protected $fillable = [
        'sid',
        'schid',
        'fname',
        'mname',
        'lname',
        'sch3',
        'count',
        'year',
        'term',
        's_basic',
        's_medical',
        's_parent',
        's_academic',
        'stat',
        'cuid',
        'status',
        'exit_status',
        'rfee',
        'adm_ssn',
        'adm_trm',
        'cls_of_adm',
        'date_of_adm',
        'adm_status',
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
