<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class expense extends Model
{
    protected $table = 'expense';
    protected $fillable = [
        'desc','tang', 'schid'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
