<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_general_data extends Model
{
    protected $table = 'school_general_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
         'user_id','state','lga', 'addr', 'vision', 'mission', 'values'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
