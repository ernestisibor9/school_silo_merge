<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_basic_data extends Model
{
    protected $table = 'school_basic_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'sname', 'phn', 'eml', 'pcode', 'pay'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
