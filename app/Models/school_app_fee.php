<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class school_app_fee extends Model
{
    protected $table = 'school_app_fee';
    protected $primaryKey = 'sid';
    public $incrementing = false;
    protected $fillable = [
        'sid', 'fee'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
