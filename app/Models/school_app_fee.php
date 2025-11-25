<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_app_fee extends Model
{
    protected $table = 'school_app_fee';
    protected $fillable = [
        'sid', 'fee', 'ssn', 'trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
