<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class afeerec extends Model
{
    protected $table = 'afeerec';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid','stid','schid','clsid','amt','ssn', 'trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
