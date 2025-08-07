<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class in_expenditure extends Model
{
    protected $table = 'in_expenditure';
    protected $fillable = [
        'name','purp','amt','mode','ext', 'schid', 'time', 'ssn', 'trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
