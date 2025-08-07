<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class silo_user extends Model
{
    protected $table = 'silo_user'; 
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $fillable = [
        'uid', 'eml', 'lname', 'oname', 'zone', 'dob', 'state', 'lga', 'phn', 'role', 'addr', 'prev', 
        'date', 'level', 'verif', //Use timestamp to know activeness
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
