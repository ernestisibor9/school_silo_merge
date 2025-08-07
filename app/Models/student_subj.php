<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_subj extends Model
{
    protected $table = 'student_subj';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid', 'sbj', 'comp', 'term', 'schid', 'ssn','clsid','trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
