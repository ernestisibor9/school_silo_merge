<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class vendor extends Model
{
    protected $table = 'vendor';
    protected $fillable = [
        'name','phn','addr','bnk','anum', 'aname', 'goods', 'schid'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
