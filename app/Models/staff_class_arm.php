<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff_class_arm extends Model
{
    protected $table = 'staff_class_arm';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid', 'cls', 'arm', 'schid','sesn','trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
