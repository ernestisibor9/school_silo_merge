<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payhead extends Model
{
    protected $table = 'payhead';
    protected $fillable = [
        'schid','name', 'comp'
    ];
    /*protected $hidden = [
        'password',
    ];*/
    
}
