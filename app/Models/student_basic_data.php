<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class student_basic_data extends Model
{
    protected $table = 'student_basic_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'dob', 'sex', 'height', 'country','state', 'lga', 'addr'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
