<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class afee extends Model
{
    protected $table = 'afee';
    protected $fillable = [
        'schid','clsid','amt'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
