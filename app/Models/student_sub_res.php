<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_sub_res extends Model
{
    protected $table = 'student_sub_res';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid','schid','ssn','trm','clsm','clsa','sbj','pos'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
