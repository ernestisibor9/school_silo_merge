<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_web_data extends Model
{
    protected $table = 'school_web_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'sname', 'color', 'addr', 'country', 'state', 'lga', 'phn','eml', 'vision', 'values', 'year', 'about', 'motto', 
        'fb', 'isg', 'yt', 'wh', 'lkd', 'tw'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
