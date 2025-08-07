<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_academic_data extends Model
{
    protected $table = 'student_academic_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'last_school', 'last_class', 'new_class','new_class_main'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
