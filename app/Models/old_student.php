<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class old_student extends Model
{
    protected $table = 'old_student';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid',
        'sid',
        'schid',
        'fname',
        'mname',
        'lname',
        'suid',
        'ssn',
        'trm',
        'clsm',
        'clsa',
        'more'
    ];
    /*protected $hidden = [
        'password',
    ];*/

    public function student()
    {
        return $this->belongsTo(student::class, 'sid', 'sid');
    }

    public function academicData()
    {
        return $this->belongsTo(student_academic_data::class, 'sid', 'user_id');
    }
}
