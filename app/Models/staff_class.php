<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class staff_class extends Model
{
    protected $table = 'staff_class';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid', 'cls', 'schid'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
