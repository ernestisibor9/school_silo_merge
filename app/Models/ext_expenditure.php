<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ext_expenditure extends Model
{
    protected $table = 'ext_expenditure';
    protected $fillable = [
        'vendor','item','pv','dets','name','phn','unit','qty','mode','ext', 'schid', 'time', 'ssn', 'trm'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
