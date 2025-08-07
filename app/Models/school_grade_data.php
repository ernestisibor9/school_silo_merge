<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_grade_data extends Model
{
    protected $table = 'school_grade_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'a_h', 'a_l','b2_h', 'b2_l','b3_h', 'b3_l','c4_h', 'c4_l','c5_h', 'c5_l','c6_h', 'c6_l', 'd7_h', 'd7_l',
        'e8_h', 'e8_l', 'f_h', 'f_l'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
