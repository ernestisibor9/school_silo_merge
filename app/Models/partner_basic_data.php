<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class partner_basic_data extends Model
{
    protected $table = 'partner_basic_data';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $fillable = [
        'user_id', 'fname', 'mname', 'lname', 'phn', 'eml', 'verif', 'pcode'
    ];
    /*protected $hidden = [
        'password',
    ];*/
}
