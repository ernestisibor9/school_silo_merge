<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pay extends Model
{
    protected $table = 'pay';
    protected $fillable = [
        'sid','rid','sname','amt','time', 'ref', 'typ','pid'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
