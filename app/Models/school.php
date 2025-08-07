<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school extends Model
{
    protected $table = 'school';
    protected $primaryKey = 'sid';
    public $incrementing = false;
    protected $fillable = [
        'sid', 'name', 'count','s_web', 's_info', 'sbd','sch3','cssn', 'ctrm', 'ctrmn'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
