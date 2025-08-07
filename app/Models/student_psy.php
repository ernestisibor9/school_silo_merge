<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_psy extends Model
{
    protected $table = 'student_psy';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid','schid','ssn','trm','clsm','clsa','punc', 'hon', 'pol','neat','pers','rel','dil','cre', 'pat', 'verb','gam','musc','drw','wrt'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
