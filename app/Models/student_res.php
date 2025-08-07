<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_res extends Model
{
    protected $table = 'student_res';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid','schid','ssn','trm','clsm','clsa','stat', 'com','pos','avg','cavg'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
