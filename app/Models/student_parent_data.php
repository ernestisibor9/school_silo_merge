<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_parent_data extends Model
{
    protected $table = 'student_parent_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'fname', 'lname', 'mname', 'sex','phn', 'eml', 'relation', 'job', 'addr', 'origin', 'residence'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
