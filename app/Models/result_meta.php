<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class result_meta extends Model
{
    protected $table = 'result_meta';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','schid', 'ssn', 'trm', 'ntrd', 'sdob', 'num_of_days', 'spos', 'subj_pos'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
